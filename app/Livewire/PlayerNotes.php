<?php

namespace App\Livewire;

use App\Models\PlayerNote;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Le note dei DM su un giocatore, nella scheda di ogni suo personaggio. */
class PlayerNotes extends Component
{
    #[Locked]
    public int $playerId;

    public string $testo = '';

    public function mount(User $player): void
    {
        $this->authorize('viewAbout', [PlayerNote::class, $player]);
        $this->playerId = $player->getKey();
    }

    public function aggiungi(): void
    {
        $player = User::findOrFail($this->playerId);
        $this->authorize('viewAbout', [PlayerNote::class, $player]);

        $this->validate(['testo' => ['required', 'string', 'max:1000']], [
            'testo.required' => 'Scrivi qualcosa prima di salvare.',
        ]);

        $nota = new PlayerNote(['body' => trim($this->testo)]);
        $nota->forceFill(['user_id' => $player->getKey(), 'author_id' => auth()->id()])->save();

        $this->reset('testo');
    }

    public function elimina(int $notaId): void
    {
        $nota = PlayerNote::where('user_id', $this->playerId)->findOrFail($notaId);
        $this->authorize('delete', $nota);

        $nota->delete();
    }

    public function render()
    {
        $player = User::findOrFail($this->playerId);

        return view('livewire.player-notes', [
            'player' => $player,
            'note' => PlayerNote::with('author')->where('user_id', $this->playerId)->latest()->get(),
        ]);
    }
}
