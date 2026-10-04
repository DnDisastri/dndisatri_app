<?php

namespace App\Livewire\Concerns;

use App\Livewire\Market\Listings;
use App\Livewire\Market\Shop;
use App\Livewire\Market\Trades;
use App\Models\Character;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

/**
 * Il personaggio con cui si usa il mercato. L'id arriva dal browser: è innocuo
 * perché il personaggio si cerca sempre fra i propri.
 *
 * Le tre sezioni stanno sulla stessa pagina e si avvisano a vicenda quando
 * cambia il personaggio o un'azione ne cambia oro e zaino.
 */
trait ActsAsCharacter
{
    public ?int $characterId = null;

    /** Solo nella sezione che ha agito, e solo nella risposta a quell'azione. */
    public ?string $esito = null;

    public function hydrateActsAsCharacter(): void
    {
        $this->esito = null;
    }

    public function updatedActsAsCharacter(string $proprieta): void
    {
        if ($proprieta === 'characterId') {
            $this->avvisaLeAltre('mercato-personaggio', ['id' => $this->characterId]);
        }
    }

    #[On('mercato-personaggio')]
    public function seguiPersonaggio(?int $id): void
    {
        $this->characterId = $id;
    }

    #[On('mercato-cambiato')]
    public function ridisegna(): void {}

    protected function esito(string $messaggio): void
    {
        $this->esito = $messaggio;
        $this->avvisaLeAltre('mercato-cambiato');
    }

    /** Non a sé stessa: si ridisegnerebbe e l'esito appena mostrato sparirebbe. */
    private function avvisaLeAltre(string $evento, array $dati = []): void
    {
        foreach ([Shop::class, Listings::class, Trades::class] as $sezione) {
            if ($sezione !== static::class) {
                $this->dispatch($evento, ...$dati)->to($sezione);
            }
        }
    }

    /** @return Collection<int,Character> */
    public function myCharacters(): Collection
    {
        return auth()->user()?->characters()->alive()->orderBy('name')->get() ?? collect();
    }

    protected function resolveCharacter(): void
    {
        $this->characterId ??= $this->myCharacters()->first()?->getKey();
    }

    protected function character(): ?Character
    {
        if ($this->characterId === null) {
            return null;
        }

        return auth()->user()
            ?->characters()
            ->alive()
            ->with(['items', 'itemEffects'])
            ->whereKey($this->characterId)
            ->first();
    }

    protected function requireCharacter(): Character
    {
        return $this->character()
            ?? abort(403, 'Serve un personaggio vivo per usare il mercato.');
    }
}
