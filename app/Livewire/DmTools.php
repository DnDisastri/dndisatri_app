<?php

namespace App\Livewire;

use App\Actions\Characters\KillCharacter;
use App\Actions\Market\GrantCoins;
use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\GameSession;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * Monete e morte dalla scheda: comandi diretti di chi conduce, non proposte.
 * Il permesso sta nella policy, chiesta qui e dal blade che disegna il componente.
 */
class DmTools extends Component
{
    #[Locked]
    public int $characterId;

    public bool $modaleOro = false;

    /** @var array<string,int|string|null> pila => quantità */
    public array $oroMonete = [];

    public bool $oroTogli = false;

    public string $oroMotivo = '';

    public ?string $esitoOro = null;

    public bool $modaleMorte = false;

    public string $morteRacconto = '';

    public ?int $morteSessione = null;

    /** L'irreversibile si spunta a mano: è la conferma esplicita che serve. */
    public bool $morteCapito = false;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    private function character(): Character
    {
        return Character::findOrFail($this->characterId);
    }

    // === Monete ===

    public function apriOro(): void
    {
        $this->authorize('grant', $this->character());
        $this->reset('oroMonete', 'oroTogli', 'oroMotivo', 'esitoOro');
        $this->resetErrorBag();
        $this->modaleOro = true;
    }

    public function annullaOro(): void
    {
        $this->modaleOro = false;
    }

    /** Il motivo è obbligatorio: finisce nel Registro, e senza mesi dopo il movimento è un mistero. */
    public function assegnaOro(): void
    {
        $character = $this->character();
        $this->authorize('grant', $character);

        $this->validate([
            'oroMonete.*' => ['nullable', 'integer', 'min:0', 'max:'.Coins::MAX],
            'oroMotivo' => ['required', 'string', 'max:200'],
        ], [
            'oroMotivo.required' => 'Il motivo serve: finisce nel Registro.',
        ]);

        $monete = Coins::fromArray($this->oroMonete);

        if ($monete->isEmpty()) {
            $this->addError('oroMonete', 'Scrivi quante monete.');

            return;
        }

        $azione = app(GrantCoins::class);

        try {
            $aggiornato = $this->oroTogli
                ? $azione->take($character, $monete, auth()->user(), $this->oroMotivo)
                : $azione->give($character, $monete, auth()->user(), $this->oroMotivo);
        } catch (MarketException $e) {
            $this->addError('oroMonete', $e->getMessage());

            return;
        }

        $this->esitoOro = ($this->oroTogli ? 'Tolte ' : 'Assegnate ').$monete->format()
            .'. Ora ha '.$aggiornato->coins()->format().'.';

        $this->modaleOro = false;

        // Lo zaino, se è aperto, mostra la borsa.
        $this->dispatch('oro-cambiato');
    }

    // === Morte ===

    public function apriMorte(): void
    {
        $this->authorize('kill', $this->character());
        $this->reset('morteRacconto', 'morteSessione', 'morteCapito');
        $this->resetErrorBag();
        $this->modaleMorte = true;
    }

    public function annullaMorte(): void
    {
        $this->modaleMorte = false;
    }

    /** Irreversibile: si passa solo con la spunta. Racconto e sessione sono facoltativi. */
    public function dichiaraCaduto(): void
    {
        $character = $this->character();
        $this->authorize('kill', $character);

        $this->validate([
            'morteCapito' => ['accepted'],
            'morteRacconto' => ['nullable', 'string', 'max:2000'],
            'morteSessione' => ['nullable', 'integer', 'exists:game_sessions,id'],
        ], [
            'morteCapito.accepted' => 'Spunta la conferma: la morte non si annulla.',
        ]);

        $sessione = $this->morteSessione !== null
            ? GameSession::find($this->morteSessione)
            : null;

        try {
            app(KillCharacter::class)->handle(
                $character, auth()->user(), $this->morteRacconto ?: null, $sessione,
            );
        } catch (RuntimeException $e) {
            $this->addError('morteRacconto', $e->getMessage());

            return;
        }

        $this->redirect(route('characters.show', $character), navigate: true);
    }

    public function render()
    {
        return view('livewire.dm-tools', [
            'character' => $this->character(),
            'sessioni' => GameSession::with('campaign')->orderByDesc('played_at')->get(),
        ]);
    }
}
