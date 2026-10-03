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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id', 'name', 'class', 'subclass', 'race', 'subrace', 'background', 'story',
    'level', 'hit_die', 'str', 'dex', 'con', 'int', 'wis', 'cha',
    'speed', 'hp_max', 'hp_current', 'hp_temp', 'gp',
    'death_save_successes', 'death_save_failures',
    'saving_throws', 'skills', 'spell_slots_used', 'spell_ability',
    'species_traits', 'class_features', 'subclass_features', 'background_feature', 'notes',
])]
class Character extends Model
{
    use HasFactory, LogsActivity;

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

    /** Null se è caduto fra una sessione e l'altra. */
    public function diedInSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'died_in_session_id');
    }

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

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /** Le presenze sono del personaggio (`character_id`), non del giocatore. */
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

    /** Semplificazione: un multiclasse avrebbe dadi di facce diverse, qui ne conta uno solo. */
    public function hitDiceTotal(): int
    {
        return max(1, (int) $this->level);
    }

    public function hitDiceLeft(): int
    {
        return max(0, $this->hitDiceTotal() - (int) $this->hit_dice_used);
    }

    // === I preferiti dell'emporio ===

    public function favoriteItems(): BelongsToMany
    {
        return $this->belongsToMany(MarketItem::class)
            ->orderBy('category')
            ->orderBy('name');
    }

    /** Ritorna true se la stella è stata messa. */
    public function toggleFavorite(MarketItem $item): bool
    {
        $esito = $this->favoriteItems()->toggle($item);

        $this->unsetRelation('favoriteItems');

        return filled($esito['attached']);
    }

    /** Usa la relazione già caricata: nella griglia evita una query per stella. */
    public function hasFavorite(MarketItem|int $item): bool
    {
        $id = $item instanceof MarketItem ? $item->getKey() : $item;

        if ($this->relationLoaded('favoriteItems')) {
            return $this->favoriteItems->contains('id', $id);
        }

        return $this->favoriteItems()->whereKey($id)->exists();
    }

    // === Classi ===

    /** Limite della casa: il manuale non ne pone. */
    public const MAX_CLASSES = 3;

    public function classes(): HasMany
    {
        return $this->hasMany(CharacterClass::class)
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }

    /**
     * Senza righe di classe (personaggio in memoria, test) ricade sulla copia della scheda.
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

    /** Accorpa solo con le righe in zaino: quelle equipaggiate hanno l'indice univoco sullo slot. */
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

    /** Restituisce quanti ne ha tolti davvero. */
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

    private const SENZA_SOTTORAZZA = 'Nessuna';

    /** Alcune sottorazze contengono già la razza («Elfo Alto»), altre no («Piedelesto»). */
    public function speciesLabel(): string
    {
        if (blank($this->subrace) || $this->subrace === self::SENZA_SOTTORAZZA) {
            return (string) $this->race;
        }

        return str_contains($this->subrace, (string) $this->race)
            ? $this->subrace
            : "{$this->race} {$this->subrace}";
    }

    // === Registro ===

    /** Il massimo di `unsignedInteger` (`gp` e mercato): oltre, il database risponde con un 500. */
    public const MAX_GP = 4_294_967_295;

    /** Va chiamata DOPO aver aggiornato l'oro, o `gp_after` è sbagliato. */
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
            // La colonna regge testi lunghi; il taglio tiene leggibile il Registro.
            'message' => Str::limit($message, 2000),
            // I dati per annullare il movimento, se non stanno già altrove.
            'details' => $details,
        ]);
    }

    public function equipped(EquipmentSlot $slot): ?CharacterItem
    {
        return $this->items->firstWhere('equipped_slot', $slot);
    }

    // === Stato ===

    public function isAlive(): bool
    {
        return $this->died_at === null;
    }

    public function isDying(): bool
    {
        return $this->isAlive() && $this->hp_current <= 0;
    }

    /** Ricliccare il pallino acceso lo spegne (torna a n-1). Unica logica per scheda e tracker. */
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

    /** Senza oggetti magici: i calcoli di gioco partono da effectiveScores(). */
    public function baseScores(): AbilityScores
    {
        return AbilityScores::fromArray($this->only(['str', 'dex', 'con', 'int', 'wis', 'cha']));
    }

    public const ATTUNEMENT_LIMIT = 3;

    /**
     * Un effetto vale se il suo oggetto è in sintonia; senza oggetto (benedizioni,
     * maledizioni) vale sempre.
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

    public function effectiveScores(): AbilityScores
    {
        return $this->baseScores()->withEffects(
            $this->activeEffects()->map(fn (CharacterItemEffect $effect) => $effect->toDomain())
        );
    }

    /** Un oggetto che altera la Costituzione sposta il massimo; il valore salvato non cambia. */
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

    public function rank(): AdventurerRank
    {
        return AdventurerRank::fromLevel($this->level);
    }

    /** Non c'è una colonna: è l'ultima richiesta di passaggio approvata, o la creazione. */
    public function lastLevelUpAt(): Carbon
    {
        $ultimo = $this->pendingChanges()
            ->where('type', PendingChangeType::LevelUp)
            ->where('status', PendingChangeStatus::Approved)
            ->latest('updated_at')
            ->first();

        return $ultimo?->updated_at ?? $this->created_at;
    }

    public function sessionsSinceLastLevelUp(): int
    {
        return $this->sessions()
            ->where('played_at', '<', now())
            ->where('played_at', '>', $this->lastLevelUpAt())
            ->count();
    }

    public function canRequestLevelUp(): bool
    {
        return $this->sessionsSinceLastLevelUp() >= 1;
    }

    public function casterType(): CasterType
    {
        return CasterType::for($this->class, $this->subclass);
    }

    /** Sempre da `Multiclass`, anche con una classe: con più classi il livello da incantatore si combina. */
    public function spellSlots(): SpellSlotSet
    {
        return Multiclass::slots($this->classLevels());
    }

    /** Riserva distinta: torna anche col riposo breve. */
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

    /** Sempre calcolata: non esiste una colonna `ac`. */
    public function armorClass(): int
    {
        return ArmorClass::compute(
            $this->effectiveScores(),
            $this->equipped(EquipmentSlot::Armor)?->name,
            $this->equipped(EquipmentSlot::Shield)?->name,
        );
    }

    // === Incantesimi preparati ===

    public function preparesSpells(): bool
    {
        return collect(array_keys($this->classLevels()))->contains(
            fn (string $class) => ClassRules::prepares($class)
        );
    }

    /** `modificatore + livello nella classe`, minimo uno; con più classi i budget si sommano. */
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
     * I trucchetti valgono sempre: non si preparano.
     *
     * @return Collection<int, CharacterSpell>
     */
    public function activeSpells(): Collection
    {
        return $this->spells->filter(
            fn (CharacterSpell $spell) => $spell->isCantrip() || $spell->prepared
        );
    }

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

    public function savingThrow(Ability $ability): int
    {
        return Checks::savingThrow(
            $this->effectiveScores(),
            $ability,
            (bool) ($this->saving_throws[$ability->value] ?? false),
            $this->proficiencyBonus(),
        );
    }

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
     * Dall'inventario: un'arma venduta sparisce da sé. `character_weapons` non
     * duplica le armi, le corregge (spada +1, arma fuori catalogo).
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

                // Sulla correzione è già un Ability (cast del modello); nel catalogo è una stringa.
                $ability = $override?->attack_ability
                    ?? Ability::from($catalog['stat'] ?? Ability::Str->value);
                $bonus = (int) ($override->weapon_bonus ?? 0);

                $attack = Checks::weaponAttack($scores, $ability, $proficiency, $bonus);
                $damageDie = $override->damage ?? $catalog['damage'] ?? 'Vuoto';
                $damageMod = $scores->modifier($ability) + $bonus;

                // Una correzione può avere il modificatore già scritto ("1d4+3"): non si somma due volte.
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
            ->sortByDesc('equipped')
            ->values();
    }
}
