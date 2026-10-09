<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\FocusesCampaign;
use App\Models\Campaign;
use App\Models\Npc;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** I PNG ricorrenti della campagna a fuoco: li crea e li modifica qualsiasi DM, dall'app. */
#[Title('PNG')]
class NpcManager extends Component
{
    use FocusesCampaign, WithFileUploads;

    #[Url(as: 'campagna')]
    public string $campagna = '';

    #[Url(as: 'cerca', except: '')]
    public string $cerca = '';

    public ?int $modificaId = null;

    public bool $aperto = false;

    /** @var array{name: string, location: ?string, wants: ?string, notes: ?string} */
    public array $png = ['name' => '', 'location' => null, 'wants' => null, 'notes' => null];

    /** @var TemporaryUploadedFile|null */
    public $foto = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Npc::class);
    }

    private function corrente(): ?Campaign
    {
        [$mie, $altre] = $this->campagneDelDm(auth()->user());

        return $this->campagnaAFuoco($this->campagna, $mie, $altre);
    }

    public function nuovo(): void
    {
        $this->authorize('create', Npc::class);
        $this->reset('modificaId', 'png', 'foto');
        $this->resetErrorBag();
        $this->aperto = true;
    }

    public function modifica(int $id): void
    {
        $npc = $this->trova($id);
        $this->authorize('update', $npc);

        $this->modificaId = $npc->id;
        $this->png = $npc->only(['name', 'location', 'wants', 'notes']);
        $this->foto = null;
        $this->resetErrorBag();
        $this->aperto = true;
    }

    public function chiudi(): void
    {
        $this->aperto = false;
    }

    public function salva(): void
    {
        $campagna = $this->corrente() ?? abort(404);

        $dati = $this->validate([
            'png.name' => ['required', 'string', 'max:100'],
            'png.location' => ['nullable', 'string', 'max:150'],
            'png.wants' => ['nullable', 'string', 'max:255'],
            'png.notes' => ['nullable', 'string', 'max:5000'],
            'foto' => ['nullable', 'image', 'max:4096'],
        ], ['png.name.required' => 'Serve un nome.'])['png'];

        $npc = $this->modificaId ? $this->trova($this->modificaId) : new Npc;
        $this->authorize($npc->exists ? 'update' : 'create', $npc->exists ? $npc : Npc::class);

        $npc->fill($dati);
        $npc->campaign_id ??= $campagna->id;
        $npc->created_by ??= auth()->id();

        if ($this->foto) {
            if ($npc->photo_path) {
                Storage::disk('public')->delete($npc->photo_path);
            }

            $npc->photo_path = $this->foto->store('npcs', 'public');
        }

        $npc->save();

        $this->aperto = false;
        $this->reset('foto');
    }

    public function elimina(int $id): void
    {
        $npc = $this->trova($id);
        $this->authorize('delete', $npc);

        if ($npc->photo_path) {
            Storage::disk('public')->delete($npc->photo_path);
        }

        $npc->delete();
        $this->aperto = false;
    }

    /** Solo fra i PNG della campagna a fuoco: un id arrivato dal browser non apre gli altri. */
    private function trova(int $id): Npc
    {
        return Npc::where('campaign_id', $this->corrente()?->id)->findOrFail($id);
    }

    public function render()
    {
        $campagna = $this->corrente();

        $png = $campagna
            ? $campagna->npcs()
                ->when(trim($this->cerca) !== '', fn ($q) => $q->search(trim($this->cerca)))
                ->orderBy('name')->get()
            : collect();

        return view('livewire.npc-manager', [
            'campagnaCorrente' => $campagna,
            'elenco' => $png,
        ]);
    }
}
