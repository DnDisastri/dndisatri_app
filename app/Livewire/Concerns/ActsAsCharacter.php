<?php

namespace App\Livewire\Concerns;

use App\Models\Character;
use Illuminate\Support\Collection;

/**
 * Il personaggio con cui si sta usando il mercato.
 *
 * Serve perché un DM gioca anche lui e può avere più personaggi vivi: al
 * mercato ci va con uno alla volta, e deve poter scegliere quale.
 *
 * **La sicurezza non sta nel non poter cambiare l'id.** L'id arriva dal browser
 * e può essere manomesso: quello che lo rende innocuo è che il personaggio si
 * cerca sempre **fra i propri**, quindi un id altrui semplicemente non viene
 * trovato.
 */
trait ActsAsCharacter
{
    public ?int $characterId = null;

    /** @return Collection<int,Character> */
    public function myCharacters(): Collection
    {
        return auth()->user()?->characters()->alive()->orderBy('name')->get() ?? collect();
    }

    protected function resolveCharacter(): void
    {
        $this->characterId ??= $this->myCharacters()->first()?->getKey();
    }

    /** Il personaggio attivo, o null se chi guarda non ne ha (un admin). */
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

    /** Il personaggio attivo, o un errore se non ce n'è: per le azioni. */
    protected function requireCharacter(): Character
    {
        return $this->character()
            ?? abort(403, 'Serve un personaggio vivo per usare il mercato.');
    }
}
