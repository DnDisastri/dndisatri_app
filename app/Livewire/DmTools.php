<?php

namespace App\Livewire;

use App\Actions\Characters\KillCharacter;
use App\Actions\Market\GrantGold;
use App\Models\Character;
use App\Models\GameSession;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * Gli strumenti che chi conduce ha sulla scheda (M17): oro e morte (M18, M19).
 *
 * Sono comandi **diretti**, non proposte: un DM non chiede, corregge. Stanno
 * qui e non nel Pannello perché sono gesti da tavolo — l'oro è il gesto di fine
 * serata («chiuso l'incarico, si paga»), la morte succede mentre si gioca.
 *
 * La logica esisteva già ed è testata (`GrantGold`, `KillCharacter`): questo è
 * solo il bottone che mancava. Il permesso non sta nel non poter premere: sta
 * nella policy, interrogata sia qui sia dal blade che decide se disegnare il
 * componente. Su un personaggio caduto non compare niente, per nessuno.
 */
class DmTools extends Component
{
    #[Locked]
    public int $characterId;

    // Assegna oro (M18).
    public bool $modaleOro = false;

    public ?int $oroImporto = null;

    public string $oroMotivo = '';

    /** L'ultima assegnazione, per dirlo senza ricaricare la pagina. */
    public ?string $esitoOro = null;

    // Dichiara caduto (M19).
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

    // === Oro (M18) ===

    public function apriOro(): void
    {
        $this->authorize('grant', $this->character());
        $this->reset('oroImporto', 'oroMotivo', 'esitoOro');
        $this->resetErrorBag();
        $this->modaleOro = true;
    }

    public function annullaOro(): void
    {
        $this->modaleOro = false;
    }

    /**
     * Il motivo **non è facoltativo**: finisce nel Registro e lì resta. Un
     * movimento d'oro senza una ragione scritta, mesi dopo, è un mistero.
     */
    public function assegnaOro(): void
    {
        $character = $this->character();
        $this->authorize('grant', $character);

        $this->validate([
            'oroImporto' => ['required', 'integer', 'not_in:0'],
            'oroMotivo' => ['required', 'string', 'max:200'],
        ], [
            'oroImporto.required' => 'Scrivi quanto oro.',
            'oroImporto.not_in' => 'Zero non assegna niente.',
            'oroMotivo.required' => 'Il motivo serve: finisce nel Registro.',
        ]);

        $aggiornato = app(GrantGold::class)->handle(
            $character, $this->oroImporto, auth()->user(), $this->oroMotivo,
        );

        $mosso = $this->oroImporto >= 0 ? 'Assegnati' : 'Sottratti';
        $this->esitoOro = "{$mosso} ".abs($this->oroImporto)." mo. Ora ha {$aggiornato->gp} mo.";

        $this->modaleOro = false;

        // Lo zaino, se è aperto, mostra l'oro: che si aggiorni da sé.
        $this->dispatch('oro-cambiato');
    }

    // === Morte (M19) ===

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

    /**
     * Irreversibile, e trattato come tale: si passa di qui solo con la spunta
     * di conferma. Il racconto e la serata sono facoltativi — qualcuno muore
     * fra una sessione e l'altra, e il racconto si può scrivere dopo (P15b).
     */
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

        // La scheda si ricarica sullo stato di caduto: i comandi spariscono, il
        // segno della morte compare, e da lì si va al memoriale.
        $this->redirect(route('characters.show', $character), navigate: true);
    }

    public function render()
    {
        return view('livewire.dm-tools', [
            'character' => $this->character(),
            // Le serate, dalla più recente: si sceglie quella in cui è successo.
            'sessioni' => GameSession::with('campaign')->orderByDesc('played_at')->get(),
        ]);
    }
}
