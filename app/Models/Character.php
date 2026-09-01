<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\AdventurerRank;
use App\Domain\Dnd\ArmorClass;
use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\Checks;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\HitPoints;
use App\Domain\Dnd\Multiclass;
use App\Domain\Dnd\Progression;
use App\Domain\Dnd\SkillProficiency;
use App\Domain\Dnd\SpellSlots;
use App\Domain\Dnd\SpellSlotSet;
use App\Enums\EquipmentSlot;
use App\Enums\LedgerAction;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id', 'name', 'class', 'subclass', 'race', 'background', 'story',
    'level', 'hit_die', 'str', 'dex', 'con', 'int', 'wis', 'cha',
    'speed', 'hp_max', 'hp_current', 'hp_temp', 'gp',
    'death_save_successes', 'death_save_failures',
    'saving_throws', 'skills', 'spell_slots_used', 'spell_ability',
    'species_traits', 'class_features', 'subclass_features', 'background_feature', 'notes',
])]
class Character extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Ogni modifica alla scheda finisce nel registro attività, con chi l'ha
     * fatta e cosa è cambiato. Vale sia per le approvazioni sia per gli
     * interventi diretti dei DM.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('personaggio')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'saving_throws' => 'array',
            'skills' => 'array',
            'spell_slots_used' => 'array',
            'died_at' => 'datetime',
            'speed' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La serata in cui è morto, quando è morto a un tavolo.
     *
     * Nulla per chi è caduto fra una sessione e l'altra: succede, e non c'è
     * niente da collegare.
     */
    public function diedInSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'died_in_session_id');
    }

    /**
     * L'indirizzo della foto, o null se non ne ha una.
     *
     * Chi la mostra decide cosa fare del segnaposto: qui non si inventa un
     * percorso finto, perché «non ha una foto» è un'informazione.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null;
    }

    // === Relazioni della scheda ===

    public function items(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    public function weapons(): HasMany
    {
        return $this->hasMany(CharacterWeapon::class);
    }

    public function feats(): HasMany
    {
        return $this->hasMany(CharacterFeat::class);
    }

    public function itemEffects(): HasMany
    {
        return $this->hasMany(CharacterItemEffect::class);
    }

    public function spells(): HasMany
    {
        return $this->hasMany(CharacterSpell::class);
    }

    public function pendingChanges(): HasMany
    {
        return $this->hasMany(PendingChange::class);
    }

    /** Il Registro dei movimenti di oro e oggetti. */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Le serate a cui questo personaggio si è seduto.
     *
     * Passa da `character_id` sul pivot delle presenze e non da chi lo gioca:
     * è la differenza fra «Marco c'era» e «Grimm c'era», e per la pagina di una
     * campagna conta la seconda — un giocatore che perde un eroe a metà storia
     * non fa risultare il successivo a serate che non ha visto.
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            GameSession::class,
            'game_session_user',
            'character_id',
            'game_session_id',
        );
    }

    // === I dadi vita ===

    /**
     * Quanti dadi vita ha in tutto: uno per livello.
     *
     * Un multiclasse ne avrebbe di facce diverse — un Guerriero 3 / Mago 2 ha
     * tre d10 e due d6 — ma la scheda tiene una faccia sola. È la stessa
     * semplificazione delle due riserve di slot, ed è scritta fra le cose che
     * mancano.
     */
    public function hitDiceTotal(): int
    {
        return max(1, (int) $this->level);
    }

    /** Quanti gliene restano da spendere. */
    public function hitDiceLeft(): int
    {
        return max(0, $this->hitDiceTotal() - (int) $this->hit_dice_used);
    }

    // === I preferiti dell'emporio ===

    /**
     * Gli articoli dell'emporio segnati da questo personaggio.
     *
     * Sono una scorciatoia per le cose che si ricomprano ogni volta — pozioni,
     * frecce, razioni — e stanno sul personaggio perché è lui a consumarle.
     */
    public function favoriteItems(): BelongsToMany
    {
        return $this->belongsToMany(MarketItem::class)
            ->orderBy('category')
            ->orderBy('name');
    }

    /**
     * Mette o toglie la stella, e dice com'è finita.
     *
     * `toggle()` fa tutto in una query per parte e non ha bisogno di sapere
     * prima cosa c'era: quello che ritorna sono le due liste, e basta guardare
     * se l'id è finito fra gli attaccati.
     */
    public function toggleFavorite(MarketItem $item): bool
    {
        $esito = $this->favoriteItems()->toggle($item);

        $this->unsetRelation('favoriteItems');

        return filled($esito['attached']);
    }

    /**
     * La relazione già caricata vince sulla query: nella griglia dell'emporio
     * la domanda si ripete per ogni articolo, e senza questo sarebbero venti
     * interrogazioni per disegnare venti stelle.
     */
    public function hasFavorite(MarketItem|int $item): bool
    {
        $id = $item instanceof MarketItem ? $item->getKey() : $item;

        if ($this->relationLoaded('favoriteItems')) {
            return $this->favoriteItems->contains('id', $id);
        }

        return $this->favoriteItems()->whereKey($id)->exists();
    }

    // === Classi (D14, D17) ===

    /** Quante classi si possono avere (D19): il manuale non pone limiti. */
    public const MAX_CLASSES = 3;

    /** Le classi, la principale per prima. */
    public function classes(): HasMany
    {
        return $this->hasMany(CharacterClass::class)
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }

    /**
     * Classe => livello, la forma che le regole del multiclasse si aspettano.
     *
     * Se le righe non ci sono ancora — un personaggio appena costruito in
     * memoria, o un test che non le crea — si ricade sulla copia tenuta sulla
     * scheda, che per un monoclasse dice la stessa cosa.
     *
     * @return array<string,int>
     */
    public function classLevels(): array
    {
        $rows = $this->relationLoaded('classes') ? $this->classes : $this->classes()->get();

        if ($rows->isEmpty()) {
            return $this->class === null ? [] : [$this->class => (int) $this->level];
        }

        return $rows->pluck('level', 'class')->all();
    }

    public function primaryClass(): ?CharacterClass
    {
        return $this->classes()->primary()->first();
    }

    public function levelIn(string $class): int
    {
        return (int) ($this->classLevels()[$class] ?? 0);
    }

    public function isMulticlass(): bool
    {
        return count($this->classLevels()) > 1;
    }

    // === Inventario ===

    /**
     * Aggiunge un oggetto, accorpandolo a uno uguale già in zaino.
     *
     * Non tocca mai le righe equipaggiate: comprare una seconda cotta di
     * maglia non deve accodarsi a quella indossata, o l'indice univoco sullo
     * slot verrebbe coinvolto in operazioni che non lo riguardano.
     */
    public function addToInventory(string $name, int $qty = 1, ?string $category = null, int $value = 0, ?string $details = null): CharacterItem
    {
        $existing = $this->items()
            ->where('name', $name)
            ->whereNull('equipped_slot')
            ->first();

        if ($existing !== null) {
            $existing->increment('qty', $qty);

            return $existing->refresh();
        }

        return $this->items()->create([
            'name' => $name,
            'category' => $category,
            'qty' => $qty,
            'value' => $value,
            'details' => $details,
        ]);
    }

    /**
     * Toglie una quantità di un oggetto dall'inventario, partendo da quelli
     * riposti. Restituisce quanti ne ha effettivamente tolti.
     */
    public function removeFromInventory(string $name, int $qty = 1): int
    {
        $removed = 0;

        $rows = $this->items()
            ->where('name', $name)
            // Prima quelli in zaino: si toglie l'equipaggiato solo se serve.
            ->orderByRaw('equipped_slot IS NOT NULL')
            ->get();

        foreach ($rows as $row) {
            if ($removed >= $qty) {
                break;
            }

            $take = min($row->qty, $qty - $removed);
            $removed += $take;

            $row->qty === $take
                ? $row->delete()
                : $row->decrement('qty', $take);
        }

        return $removed;
    }

    public function ownsItem(string $name, int $qty = 1): bool
    {
        return $this->items()->where('name', $name)->sum('qty') >= $qty;
    }

    // === Registro ===

    /**
     * Scrive una riga nel Registro. Va chiamata DOPO aver aggiornato l'oro,
     * così `gp_after` racconta il saldo risultante.
     */
    public function recordInLedger(
        LedgerAction $action,
        string $message,
        int $gpDelta = 0,
        ?User $actor = null,
        ?array $details = null,
    ): LedgerEntry {
        return $this->ledgerEntries()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'gp_delta' => $gpDelta,
            'gp_after' => $this->gp,
            'message' => $message,
            // Quel che serve per tornare indietro, quando il resto del sistema
            // non lo conserva già altrove (vedi la migrazione di D12).
            'details' => $details,
        ]);
    }

    /** L'oggetto equipaggiato in uno slot, se c'è. */
    public function equipped(EquipmentSlot $slot): ?CharacterItem
    {
        return $this->items->firstWhere('equipped_slot', $slot);
    }

    // === Stato ===

    public function isAlive(): bool
    {
        return $this->died_at === null;
    }

    /**
     * A terra e ancora vivo: è quando contano i tiri salvezza contro morte.
     *
     * I punti ferita tengono i negativi apposta (vedi `AdjustHitPoints`), quindi
     * «a terra» è zero **o sotto**, non l'uguaglianza secca.
     */
    public function isDying(): bool
    {
        return $this->isAlive() && $this->hp_current <= 0;
    }

    /**
     * Segna un tiro contro morte, con la logica del pallino: cliccare il terzo
     * quando ce ne sono già tre lo toglie (torna a due). Si può solo da
     * morente, e la logica vive qui — non nei due posti che la richiamano (la
     * scheda del giocatore e il tracker del DM), che sono due porte sullo
     * stesso dato, non due copie.
     */
    public function segnaTiroMorte(string $tipo, int $n): void
    {
        if (! $this->isDying()) {
            return;
        }

        $campo = $tipo === 'successo' ? 'death_save_successes' : 'death_save_failures';
        $attuale = (int) $this->{$campo};

        $this->forceFill([
            $campo => max(0, min(3, $attuale === $n ? $n - 1 : $n)),
        ])->save();
    }

    public function scopeAlive(Builder $query): void
    {
        $query->whereNull('died_at');
    }

    public function scopeFallen(Builder $query): void
    {
        $query->whereNotNull('died_at');
    }

    // === Valori derivati (App\Domain\Dnd) ===

    /** Punteggi BASE: creazione più ASI, senza oggetti magici. */
    public function baseScores(): AbilityScores
    {
        return AbilityScores::fromArray($this->only(['str', 'dex', 'con', 'int', 'wis', 'cha']));
    }

    /** Quanti oggetti magici si tengono in sintonia alla volta. */
    public const ATTUNEMENT_LIMIT = 3;

    /**
     * Gli effetti che contano adesso.
     *
     * Un effetto vale se l'oggetto che lo porta è **in sintonia**. Togliendo la
     * sintonia, o vendendo l'oggetto, il bonus sparisce da sé — che è quello
     * che il codice sosteneva di fare da sempre senza farlo.
     *
     * Restano fuori dalla regola le benedizioni e le maledizioni: non hanno un
     * oggetto a cui essere legate, valgono sempre, e le toglie solo un DM.
     *
     * @return Collection<int, CharacterItemEffect>
     */
    public function activeEffects(): Collection
    {
        $attuned = $this->items->where('attuned', true)->keyBy('id');

        return $this->itemEffects->filter(
            fn (CharacterItemEffect $effect) => $effect->character_item_id === null
                || $attuned->has($effect->character_item_id)
        );
    }

    public function attunedItems(): Collection
    {
        return $this->items->where('attuned', true)->values();
    }

    public function attunementSlotsLeft(): int
    {
        return max(0, self::ATTUNEMENT_LIMIT - $this->attunedItems()->count());
    }

    /**
     * Punteggi EFFICACI: i base più gli effetti attivi.
     * È da qui che parte ogni calcolo di gioco, mai da baseScores().
     */
    public function effectiveScores(): AbilityScores
    {
        return $this->baseScores()->withEffects(
            $this->activeEffects()->map(fn (CharacterItemEffect $effect) => $effect->toDomain())
        );
    }

    /**
     * PF massimi EFFICACI: se un oggetto magico altera la Costituzione, il
     * massimo si muove di (delta modificatore × livello) finché resta
     * equipaggiato. Il valore salvato non viene toccato.
     */
    public function effectiveHpMax(): int
    {
        return HitPoints::effectiveMax(
            $this->hp_max,
            $this->baseScores(),
            $this->effectiveScores(),
            $this->level,
        );
    }

    public function proficiencyBonus(): int
    {
        return Progression::proficiencyBonus($this->level);
    }

    /** Il grado d'avventuriero, dedotto dal livello: sale da solo. */
    public function rank(): AdventurerRank
    {
        return AdventurerRank::fromLevel($this->level);
    }

    /**
     * Quando è salito di livello l'ultima volta.
     *
     * Serve per contare le sessioni giocate «da allora». Non c'è una colonna
     * apposta: il momento è l'approvazione dell'ultima richiesta di passaggio
     * di livello, e chi non è mai salito parte dalla propria creazione.
     */
    public function lastLevelUpAt(): \Illuminate\Support\Carbon
    {
        $ultimo = $this->pendingChanges()
            ->where('type', PendingChangeType::LevelUp)
            ->where('status', PendingChangeStatus::Approved)
            ->latest('updated_at')
            ->first();

        return $ultimo?->updated_at ?? $this->created_at;
    }

    /**
     * Le sessioni **giocate** da quando è salito di livello l'ultima volta.
     *
     * È il numero che dice se può chiedere il prossimo: la regola del gruppo è
     * che di norma una sessione giocata dà diritto a un livello — ma resta una
     * richiesta, non un automatismo, e a decidere è il DM.
     */
    public function sessionsSinceLastLevelUp(): int
    {
        return $this->sessions()
            ->where('played_at', '<', now())
            ->where('played_at', '>', $this->lastLevelUpAt())
            ->count();
    }

    /** Di norma basta una sessione giocata per poter chiedere un livello. */
    public function canRequestLevelUp(): bool
    {
        return $this->sessionsSinceLastLevelUp() >= 1;
    }

    /** Il tipo di incantatore della classe principale, per quello che mostra. */
    public function casterType(): CasterType
    {
        return CasterType::for($this->class, $this->subclass);
    }

    /**
     * Gli slot incantesimo.
     *
     * **Passa sempre da `Multiclass`**, anche con una classe sola: quella
     * funzione con una classe sola torna alla tabella di quella classe, quindi
     * il risultato è identico e non esistono due strade diverse da tenere
     * allineate.
     *
     * Con più classi non si sommano gli slot: si calcola un livello da
     * incantatore combinato (vedi `Multiclass`).
     */
    public function spellSlots(): SpellSlotSet
    {
        return Multiclass::slots($this->classLevels());
    }

    /**
     * Gli slot da patto, che vivono a parte da quelli normali.
     *
     * Un Warlock 2 / Mago 3 ha **due** riserve distinte, che si recuperano con
     * riposi diversi: il patto torna anche col riposo breve.
     */
    public function pactSlots(): SpellSlotSet
    {
        return Multiclass::pactSlots($this->classLevels());
    }

    public function spellcastingAbility(): ?Ability
    {
        return $this->spell_ability !== null
            ? Ability::from($this->spell_ability)
            : SpellSlots::abilityFor($this->class);
    }

    /**
     * Classe Armatura: sempre calcolata dai punteggi efficaci e da ciò che il
     * personaggio indossa. Non esiste nessuna colonna `ac` da cui leggerla.
     */
    public function armorClass(): int
    {
        return ArmorClass::compute(
            $this->effectiveScores(),
            $this->equipped(EquipmentSlot::Armor)?->name,
            $this->equipped(EquipmentSlot::Shield)?->name,
        );
    }

    // === Incantesimi preparati (D16) ===

    /** Ha almeno una classe che prepara gli incantesimi ogni giorno? */
    public function preparesSpells(): bool
    {
        return collect(array_keys($this->classLevels()))->contains(
            fn (string $class) => ClassRules::prepares($class)
        );
    }

    /**
     * Quanti incantesimi può tenere preparati: `modificatore + livello nella
     * classe`, mai meno di uno.
     *
     * **Con due classi che preparano i budget si sommano**, ed è una
     * semplificazione: nel manuale sarebbero due liste separate, una per
     * classe, con la propria caratteristica. Un Chierico/Druido è raro abbastanza
     * da non giustificare due elenchi sulla scheda, e sommare non regala niente
     * — ogni classe contribuisce solo con il proprio.
     */
    public function preparationLimit(): int
    {
        $total = 0;

        foreach ($this->classLevels() as $class => $level) {
            if (! ClassRules::prepares($class)) {
                continue;
            }

            $ability = SpellSlots::abilityFor($class);
            $modifier = $ability === null ? 0 : $this->effectiveScores()->modifier($ability);

            $total += max(0, $modifier + $level);
        }

        return $this->preparesSpells() ? max(1, $total) : 0;
    }

    /**
     * Gli incantesimi che si possono lanciare adesso.
     *
     * I trucchetti ci sono sempre: non si preparano. Per le classi che non
     * preparano, `prepared` resta vero su tutto e questo elenco coincide con
     * quello conosciuto.
     *
     * @return Collection<int, CharacterSpell>
     */
    public function activeSpells(): Collection
    {
        return $this->spells->filter(
            fn (CharacterSpell $spell) => $spell->isCantrip() || $spell->prepared
        );
    }

    /** CD per resistere agli incantesimi del personaggio, se ne lancia. */
    public function spellSaveDc(): ?int
    {
        $ability = $this->spellcastingAbility();

        return $ability === null
            ? null
            : Checks::spellSaveDc($this->effectiveScores(), $ability, $this->proficiencyBonus());
    }

    public function spellAttackBonus(): ?int
    {
        $ability = $this->spellcastingAbility();

        return $ability === null
            ? null
            : Checks::spellAttack($this->effectiveScores(), $ability, $this->proficiencyBonus());
    }

    /** Bonus a un tiro salvezza, competenza compresa. */
    public function savingThrow(Ability $ability): int
    {
        return Checks::savingThrow(
            $this->effectiveScores(),
            $ability,
            (bool) ($this->saving_throws[$ability->value] ?? false),
            $this->proficiencyBonus(),
        );
    }

    /** Bonus a una prova di abilità, con competenza ed Esperto. */
    public function skillBonus(string $skill): int
    {
        return Checks::skill(
            $this->effectiveScores(),
            $skill,
            SkillProficiency::tryFrom($this->skills[$skill] ?? 'none') ?? SkillProficiency::None,
            $this->proficiencyBonus(),
        );
    }

    public function initiative(): int
    {
        return ArmorClass::initiative($this->effectiveScores());
    }

    /**
     * L'elenco degli attacchi: le armi possedute, con bonus e danni già fatti
     * (decisione D9).
     *
     * Si ricava dall'inventario, non da una lista a parte: un'arma venduta
     * sparisce dagli attacchi da sé, che è la stessa ragione per cui la Classe
     * Armatura non è una colonna salvata.
     *
     * Le righe di `character_weapons` non duplicano le armi: servono a
     * **correggerle**, quando un DM assegna una spada +1 o un'arma che nel
     * catalogo non c'è.
     *
     * @return Collection<int, array{name: string, ability: Ability, attack: int, damage: string, equipped: bool}>
     */
    public function attacks(): Collection
    {
        $overrides = $this->weapons->keyBy('name');
        $scores = $this->effectiveScores();
        $proficiency = $this->proficiencyBonus();

        return $this->items
            ->filter(fn (CharacterItem $item) => $overrides->has($item->name)
                || config("dnd.combat.weapons.{$item->name}") !== null)
            ->map(function (CharacterItem $item) use ($overrides, $scores, $proficiency) {
                $catalog = config("dnd.combat.weapons.{$item->name}", []);
                $override = $overrides->get($item->name);

                // Sulla riga di correzione la caratteristica è già un Ability,
                // perché il modello la converte; nel catalogo è una stringa.
                $ability = $override?->attack_ability
                    ?? Ability::from($catalog['stat'] ?? Ability::Str->value);
                $bonus = (int) ($override->weapon_bonus ?? 0);

                $attack = Checks::weaponAttack($scores, $ability, $proficiency, $bonus);
                $damageDie = $override->damage ?? $catalog['damage'] ?? 'Vuoto';
                $damageMod = $scores->modifier($ability) + $bonus;

                /*
                 * Il danno del catalogo è a **soli dadi** ("2d6"): il
                 * modificatore lo aggiunge l'applicazione. Ma un DM, che ha
                 * davanti una scheda dove sta scritto "1d4+3", può aver messo
                 * nell'override il danno già completo — e allora aggiungerne un
                 * altro darebbe "1d4+3+3". Se il testo porta già un suo
                 * modificatore piatto, si rispetta com'è: quello che il DM ha
                 * scritto è il danno, punto.
                 */
                $giàCompleto = (bool) preg_match('/[+-]\s*\d+\s*$/', $damageDie);

                return [
                    'name' => $item->name,
                    'ability' => $ability,
                    'attack' => $attack,
                    'damage' => ($damageMod === 0 || $giàCompleto)
                        ? $damageDie
                        : $damageDie.Ability::format($damageMod),
                    'equipped' => $item->equipped_slot === EquipmentSlot::Weapon,
                ];
            })
            // Prima quella impugnata: è quella che si usa.
            ->sortByDesc('equipped')
            ->values();
    }
}
