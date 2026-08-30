<?php

namespace App\Livewire;

use App\Actions\Characters\SpendSpellSlot;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\SpellSlotSet;
use App\Models\Character;
use App\Models\CharacterSpell;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * «Lancia un incantesimo» nel Turno: la lista di ciò che puoi lanciare **adesso**,
 * con lo spendere gli slot lì dentro.
 *
 * È un cheat sheet, non la sezione Magia: qui c'è solo il minimo per scegliere —
 * livello, tempo, gittata, il tiro e i danni — e le descrizioni per esteso
 * restano in «Magia». Gli incantesimi si dividono per come colpiscono (tiri tu,
 * o tira il bersaglio), e **sparisce** ciò che non hai slot per lanciare: un
 * cheat sheet mostra le opzioni vere, non quelle spente.
 *
 * Lo spendere è lo stesso di SpellSlotTracker (l'azione, il permesso, lo stato
 * in `spell_slots_used`): qui vive nella stessa isola della lista, così spendere
 * l'ultimo slot fa sparire l'incantesimo nello stesso istante.
 */
class CastableSpells extends Component
{
    #[Locked]
    public int $characterId;

    /** L'apertura è uno stato del componente, o un re-render la richiuderebbe. */
    public bool $aperto = false;

    /** L'incantesimo (id) per cui si sta scegliendo con che slot lanciarlo. */
    public ?int $scelta = null;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    public function spend(int|string $slot): void
    {
        $character = $this->character();
        $this->authorize('manageSlots', $character);

        try {
            app(SpendSpellSlot::class)->spend($character, $slot);
        } catch (\RuntimeException $e) {
            $this->addError('slot', $e->getMessage());
        }
    }

    public function recover(int|string $slot): void
    {
        $character = $this->character();
        $this->authorize('manageSlots', $character);

        app(SpendSpellSlot::class)->recover($character, $slot);
    }

    /**
     * Lancia un incantesimo, cioè spende uno slot per farlo.
     *
     * Se lo slot da spendere è uno solo — o l'incantesimo non si potenzia a
     * livelli superiori — si spende il più basso adatto e via, senza chiedere
     * niente: sarebbe un clic in più per una scelta che non c'è. Ma se si
     * **potenzia** e ci sono più livelli di slot liberi, la scelta cambia
     * l'effetto (più danni, più bersagli), e allora si chiede.
     */
    public function cast(int $id, int $livello, bool $potenzia): void
    {
        $adatte = collect($this->opzioniSlot($this->character()))
            ->where('livello', '>=', $livello)
            ->sortBy('livello')
            ->values();

        if ($adatte->isEmpty()) {
            return; // Senza slot la riga non ci sarebbe: niente da lanciare.
        }

        if ($adatte->count() === 1 || ! $potenzia) {
            $this->spend($adatte->first()['key']);
            $this->scelta = null;

            return;
        }

        $this->scelta = $id;
    }

    /** La scelta è fatta: spende quello slot e chiude la domanda. */
    public function castAt(int|string $slot): void
    {
        $this->spend($slot);
        $this->scelta = null;
    }

    /**
     * Gli slot liberi, come coppie chiave→livello: la chiave è quella con cui si
     * spendono (il livello, o `pact`), il livello è quello a cui lo slot lancia.
     */
    private function opzioniSlot(Character $character): array
    {
        $standard = $character->spellSlots();
        $pact = $character->pactSlots();

        if ($standard->isPact) {
            $standard = SpellSlotSet::none();
        }

        $used = $character->spell_slots_used ?? [];
        $opzioni = [];

        foreach ($standard->slots as $lvl => $tot) {
            if ($tot - (int) ($used[$lvl] ?? 0) > 0) {
                $opzioni[] = ['key' => $lvl, 'livello' => $lvl];
            }
        }

        if (! $pact->isEmpty() && $pact->total() - (int) ($used['pact'] ?? 0) > 0) {
            $opzioni[] = ['key' => 'pact', 'livello' => $pact->maxSpellLevel()];
        }

        return $opzioni;
    }

    private function character(): Character
    {
        return Character::with('spells')->findOrFail($this->characterId);
    }

    public function render()
    {
        $character = $this->character();

        // Le due riserve, come nel tracker: un Warlock puro ha già la riserva da
        // patto dentro spellSlots(), e disegnarla anche come «normale» la
        // conterebbe due volte.
        $standard = $character->spellSlots();
        $pact = $character->pactSlots();

        if ($standard->isPact) {
            $standard = SpellSlotSet::none();
        }

        $used = $character->spell_slots_used ?? [];

        // Gli slot liberi, e il livello più alto fra questi. Un incantesimo di
        // livello L si lancia con qualunque slot di livello ≥ L, quindi se il
        // massimo copre L, l'incantesimo è un'opzione.
        $opzioni = $this->opzioniSlot($character);
        $massimoLanciabile = $opzioni ? max(array_column($opzioni, 'livello')) : 0;

        $attacco = $character->spellAttackBonus();
        $cd = $character->spellSaveDc();

        // Solo i preparati e di livello (i trucchetti stanno fuori, sempre
        // pronti), ordinati per livello. Ognuno diventa una riga già fatta.
        $preparati = $character->spells
            ->where('level', '>=', 1)
            ->where('prepared', true)
            ->sortBy([['level', 'asc'], ['name', 'asc']]);

        $gruppi = ['colpo' => [], 'salvezza' => [], 'utilita' => []];
        $nascosti = 0;

        foreach ($preparati as $spell) {
            if ($spell->level > $massimoLanciabile) {
                $nascosti++;

                continue;
            }

            $chiave = match ($spell->rollKind()) {
                'attacco' => 'colpo',
                'cd' => 'salvezza',
                default => 'utilita',
            };

            $gruppi[$chiave][] = [
                'id' => $spell->id,
                'livello' => $spell->level,
                'nome' => $spell->name,
                'tempo' => $spell->castingTime(),
                'gittata' => $spell->range(),
                'roll' => $this->rollLabel($spell, $attacco, $cd),
                'danni' => $spell->damage($character->level),
                'su' => $spell->scalesUp(),
            ];
        }

        return view('livewire.castable-spells', [
            'standard' => $standard,
            'pact' => $pact,
            'used' => $used,
            'opzioni' => $opzioni,
            'canManage' => auth()->user()?->can('manageSlots', $character) ?? false,
            'gruppi' => array_filter($gruppi, fn ($righe) => $righe !== []),
            'nascosti' => $nascosti,
        ]);
    }

    /** Il numero della riga: «+7» se tiri tu, «COS 15» se tira il bersaglio. */
    private function rollLabel(CharacterSpell $spell, ?int $attacco, ?int $cd): ?string
    {
        return match ($spell->rollKind()) {
            'attacco' => $attacco !== null ? Ability::format($attacco) : null,
            'cd' => $cd !== null ? trim(($spell->saveAbility() ?? 'CD').' '.$cd) : null,
            default => null,
        };
    }
}
