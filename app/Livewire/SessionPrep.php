<?php

namespace App\Livewire;

use App\Models\GameSession;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gli appunti privati del DM sulla sessione (`dm_notes`): i giocatori non li
 * vedono, e non sono il resoconto.
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
