<?php

namespace App\Livewire;

use App\Actions\Characters\AdjustHitPoints;
use App\Actions\Characters\SpendHitDie;
use App\Actions\Characters\TakeRest;
use App\Enums\RestType;
use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * I punti ferita durante la serata (D7).
 *
 * Prima di questo pannello l'unico modo di segnare un colpo preso era una
 * proposta da far approvare a un DM. Per ogni colpo.
 */
class HitPointTracker extends Component
{
    #[Locked]
    public int $characterId;

    /** Quanto togliere o aggiungere: si digita e si preme «Applica». */
    public int $amount = 1;

    /** Se «Applica» toglie o aggiunge: lo dice l'interruttore Danni/Cure. */
    public string $modo = 'danni';

    /** Quanti punti ferita temporanei aggiungere, nel suo riquadretto a parte. */
    public int $tempAmount = 1;

    /** Il riquadretto dei temporanei è aperto (un link lo apre e lo chiude). */
    public bool $mostraTemp = false;

    /** La tendina «Riposo e dadi vita» è aperta (stato del componente, o un
     *  re-render la richiuderebbe). */
    public bool $mostraRiposo = false;

    /** Cos'ha appena fatto un riposo, se ne è stato preso uno. */
    public ?string $riposo = null;

    /**
     * Il riposo che si sta per prendere, mentre il riquadro chiede conferma.
     *
     * Un riposo **cancella lo stato di una serata** — slot spesi, dadi vita,
     * temporanei — e non si annulla. Premuto per sbaglio invece di «Cure», si
     * perde il conto di tutto quello che si era segnato. Da qui la conferma,
     * che non chiede «sei sicuro?» ma dice **cosa sta per tornare indietro**:
     * è l'unica domanda a cui si possa rispondere davvero.
     */
    public ?string $conferma = null;

    /**
     * Quanto hai fatto col dado vita, mentre il riquadro lo chiede.
     *
     * Sta per conto suo e **non** nella casella dei danni: prima il pulsante
     * riusava quella, e siccome ci sta scritto 1 di suo, «Spendi un dado vita»
     * bruciava un dado per curare un punto senza chiedere niente a nessuno.
     */
    public ?int $dadoVita = null;

    /** Se il riquadro del dado vita è aperto. */
    public bool $modaleDado = false;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    /** Apre il riquadro: non spende ancora niente, chiede il tiro. */
    public function chiediDadoVita(): void
    {
        $this->modaleDado = true;
        $this->dadoVita = null;
        $this->riposo = null;
        $this->resetErrorBag('pf');
    }

    public function annullaDadoVita(): void
    {
        $this->modaleDado = false;
        $this->dadoVita = null;
    }

    /**
     * Spendere un dado vita tirandolo, com'è durante un riposo breve.
     *
     * Il numero che si scrive è **quello che hai fatto col dado vero**, non i
     * punti che vuoi recuperare: il modificatore di Costituzione lo aggiunge
     * l'azione, che è l'unico conto che val la pena togliere di mano.
     */
    public function spendHitDie(): void
    {
        $this->spendi($this->dadoVita);
    }

    /**
     * Spendere un dado vita **e basta**, quando lo consuma un privilegio di
     * classe: la riserva cala, i punti ferita non si toccano. Cosa ci si è
     * comprato lo sa il giocatore, e non è affare dell'applicazione.
     */
    public function spendiDadoSenzaCura(): void
    {
        $this->spendi(null);
    }

    private function spendi(?int $tirato): void
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);
        $this->authorize('manageHitPoints', $character);

        try {
            $prima = $character->hp_current;
            $dopo = app(SpendHitDie::class)->handle($character, $tirato);
            $curati = $dopo->hp_current - $prima;

            $this->riposo = $tirato === null
                ? 'Dado vita speso, senza cura.'
                : "Dado vita speso: {$curati} punti ferita.";

            $this->resetErrorBag('pf');
            $this->modaleDado = false;
            $this->dadoVita = null;
        } catch (\RuntimeException $e) {
            $this->addError('pf', $e->getMessage());
        }
    }

    /** Apre la conferma: non riposa ancora, dice solo cosa succederebbe. */
    public function chiediRiposo(string $type): void
    {
        $this->conferma = RestType::from($type)->value;
        $this->riposo = null;
    }

    public function annullaRiposo(): void
    {
        $this->conferma = null;
    }

    /**
     * Cosa torna indietro con questo riposo, per **questo** personaggio.
     *
     * L'elenco è fatto di cose vere e non di promesse generiche: se non hai
     * slot spesi, «tutti gli slot indietro» non compare. Una conferma che
     * elenca anche quello che non cambia insegna a non leggerla.
     *
     * @return list<string>
     */
    public function recuperi(RestType $tipo): array
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);
        $slotSpesi = collect($character->spell_slots_used ?? [])->filter()->sum();
        $voci = [];

        if ($tipo === RestType::Long) {
            $mancanti = $character->effectiveHpMax() - $character->hp_current;

            if ($mancanti > 0) {
                $voci[] = "Punti ferita al massimo: +{$mancanti}, fino a {$character->effectiveHpMax()}.";
            }

            if ($character->hp_temp > 0) {
                $voci[] = "I {$character->hp_temp} punti ferita temporanei si azzerano.";
            }

            if ($slotSpesi > 0) {
                $voci[] = "Tornano tutti gli slot incantesimo: {$slotSpesi} spesi.";
            }

            if ((int) $character->hit_dice_used > 0) {
                $indietro = min((int) $character->hit_dice_used, max(1, intdiv($character->hitDiceTotal(), 2)));
                $voci[] = "Tornano {$indietro} dadi vita su ".$character->hit_dice_used.' spesi.';
            }

            return $voci ?: ['Sei già a posto: non cambia niente.'];
        }

        if ($character->spellSlots()->isPact && $slotSpesi > 0) {
            $voci[] = "Tornano gli slot da patto: {$slotSpesi} spesi.";
        }

        // Il breve non cura da solo, ed è la cosa che più spesso si dà per
        // scontata: qui si dice, invece di lasciarla scoprire dopo.
        $voci[] = $character->hitDiceLeft() > 0
            ? 'I punti ferita non tornano da soli: spendi un dado vita, ne hai '.$character->hitDiceLeft().'.'
            : 'I punti ferita non tornano da soli, e non ti restano dadi vita.';

        return $voci;
    }

    public function rest(string $type): void
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);
        $this->authorize('manageHitPoints', $character);

        $tipo = RestType::from($type);
        $this->conferma = null;

        app(TakeRest::class)->handle($character, $tipo);

        $this->riposo = $tipo->description();

        // Gli slot stanno in un altro componente, che senza una parola non
        // saprebbe di doversi ridisegnare.
        $this->dispatch('riposo-preso');
    }

    /** «Applica»: toglie o aggiunge secondo l'interruttore Danni/Cure. */
    public function applica(): void
    {
        $this->modo === 'cure' ? $this->heal() : $this->damage();
    }

    public function damage(): void
    {
        $this->apply(fn (Character $c) => app(AdjustHitPoints::class)->damage($c, $this->amount));
    }

    public function heal(): void
    {
        $this->apply(fn (Character $c) => app(AdjustHitPoints::class)->heal($c, $this->amount));
    }

    /** I temporanei, dal loro riquadretto: hanno una casella propria, così non
     *  si confondono con quella di Danni/Cure. Fatto, il riquadretto si chiude. */
    public function aggiungiTemporanei(): void
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);
        $this->authorize('manageHitPoints', $character);
        $this->riposo = null;

        if ($this->tempAmount < 1) {
            $this->addError('pf', 'Serve un numero maggiore di zero.');

            return;
        }

        app(AdjustHitPoints::class)->grantTemporary($character, $this->tempAmount);
        $this->mostraTemp = false;
    }

    /**
     * Segna un tiro contro morte (D7, tappa B): lo fa il giocatore quando è a
     * terra, sulla sua scheda. Lo stesso dato lo vede e lo corregge il DM dal
     * tracker di combattimento — la logica del pallino vive sul modello.
     */
    public function tiroMorte(string $tipo, int $n): void
    {
        $character = Character::findOrFail($this->characterId);
        $this->authorize('manageHitPoints', $character);
        $character->segnaTiroMorte($tipo, $n);
    }

    private function apply(callable $do): void
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);
        $this->authorize('manageHitPoints', $character);

        // Un colpo preso archivia il riposo di prima: quella riga parlava di
        // un momento che è passato.
        $this->riposo = null;

        if ($this->amount < 1) {
            $this->addError('pf', 'Serve un numero maggiore di zero.');

            return;
        }

        $do($character);
    }

    public function render()
    {
        // Il massimo **efficace** tiene conto degli oggetti in sintonia:
        // servono caricati, o il conteggio sarebbe quello sbagliato.
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);

        return view('livewire.hit-point-tracker', [
            'character' => $character,
            'max' => $character->effectiveHpMax(),
            'canManage' => auth()->user()?->can('manageHitPoints', $character) ?? false,
            // Il breve rimette a posto solo gli slot da patto: a chi non ne ha
            // non si mostra, perché per lui non farebbe niente.
            'hasPact' => $character->spellSlots()->isPact,
            // La riserva dei dadi vita: è quella che rende il riposo breve
            // utile a tutti e non solo al Warlock.
            'hitDiceLeft' => $character->hitDiceLeft(),
            'hitDiceTotal' => $character->hitDiceTotal(),
        ]);
    }
}
