<?php

namespace App\Livewire;

use App\Models\GameSession;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gli appunti privati del DM per la serata.
 *
 * Il foglio davanti allo schermo: i promemoria di chi conduce, che nessun
 * giocatore vede. Non è il resoconto — quello lo leggono loro e viene dopo. Si
 * salvano sulla serata (`dm_notes`), così la sera prima e al tavolo sono lo
 * stesso foglio.
 *
 * L'iniziativa e i punti ferita del combattimento vivono in `CombatTracker`,
 * accanto a questi appunti nella stessa pagina.
 */
class SessionPrep extends Component
{
    #[Locked]
    public int $sessionId;

    public string $note = '';

    public bool $noteSalvate = false;

    public function mount(GameSession $session): void
    {
        $this->assicuraDm();

        $this->sessionId = $session->getKey();
        $this->note = (string) ($session->dm_notes ?? '');
    }

    private function assicuraDm(): void
    {
        abort_unless(auth()->user()?->isDm() ?? false, 403);
    }

    public function salvaNote(): void
    {
        $this->assicuraDm();
        $this->validate(['note' => ['nullable', 'string', 'max:20000']]);

        GameSession::findOrFail($this->sessionId)
            ->forceFill(['dm_notes' => $this->note ?: null])
            ->save();

        // Un segno che è andata: sparisce appena si ricomincia a scrivere.
        $this->noteSalvate = true;
    }

    public function updatedNote(): void
    {
        $this->noteSalvate = false;
    }

    public function render()
    {
        return view('livewire.session-prep');
    }
}
