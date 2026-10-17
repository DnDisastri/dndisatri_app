<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Actions\Approvals\AnnounceForApproval;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Coins;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\GameSession;
use App\Models\MagicItem;
use App\Models\MarketItem;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\ChangeAwaitingApproval;
use InvalidArgumentException;

/** Le proposte senza calcoli: il passaggio di livello sta in `RequestLevelUp`. */
final class ProposeChange
{
    /** Tetti di una singola richiesta di bottino: oltre, se ne fa una seconda o si chiede al DM. */
    public const LOOT_MAX_CP = 50_000;

    public const LOOT_MAX_ITEMS = 10;

    /**
     * Si salva solo quello che cambia davvero: niente richieste vuote da esaminare.
     *
     * @param  array<string,mixed>  $proposed
     */
    public function edit(Character $character, User $requester, array $proposed): PendingChange
    {
        $diff = collect($proposed)
            ->reject(fn ($value, $field) => $this->unchanged($character, $field, $value))
            ->all();

        if ($diff === []) {
            throw new InvalidArgumentException('Non hai cambiato niente.');
        }

        return $this->create($character, $requester, PendingChangeType::CharacterEdit, [
            'diff' => $diff,
        ]);
    }

    /**
     * La nota resta fuori dal riassunto, che finisce anche nel Registro.
     *
     * @param  list<array{name: string, qty?: int, category?: string, value_cp?: int, details?: string}>  $items
     */
    public function loot(Character $character, User $requester, ?Coins $coins = null, array $items = [], ?string $note = null, ?GameSession $session = null): PendingChange
    {
        $coins ??= Coins::none();

        if ($coins->hasNegative()) {
            throw new InvalidArgumentException('Le monete non possono essere negative.');
        }

        $items = array_map(MagicItem::linkRow(...), $items);

        if ($coins->isEmpty() && $items === []) {
            throw new InvalidArgumentException('Un bottino vuoto non si registra.');
        }

        if ($coins->value() > self::LOOT_MAX_CP) {
            throw new InvalidArgumentException('Al massimo '.Coins::formatValue(self::LOOT_MAX_CP).' di monete per richiesta: per somme più alte chiedi a un dungeon master.');
        }

        if (count($items) > self::LOOT_MAX_ITEMS) {
            throw new InvalidArgumentException('Al massimo '.self::LOOT_MAX_ITEMS.' oggetti per richiesta: registra il resto con una seconda richiesta.');
        }

        $parts = array_filter([
            $coins->isEmpty() ? null : $coins->format(),
            $items !== [] ? collect($items)->map(fn ($i) => ($i['qty'] ?? 1)."× {$i['name']}")->join(', ') : null,
        ]);

        return $this->create($character, $requester, PendingChangeType::Loot, [
            'grant_coins' => $coins->isEmpty() ? null : $coins->nonZero(),
            'grant_items' => $items,
            'summary' => 'Bottino: '.implode(' e ', $parts),
            'note' => filled($note) ? $note : null,
            'game_session_id' => $session?->getKey(),
        ]);
    }

    public function itemEffect(
        Character $character,
        User $requester,
        string $name,
        Ability $ability,
        ItemEffectMode $mode,
        int $value,
    ): PendingChange {
        $verb = $mode === ItemEffectMode::Set ? 'porta a' : ($value >= 0 ? '+' : '');

        return $this->create($character, $requester, PendingChangeType::ItemEffect, [
            'diff' => [
                'name' => $name,
                'ability' => $ability->value,
                'mode' => $mode->value,
                'value' => $value,
            ],
            'summary' => "{$name}: {$ability->label()} {$verb}{$value}",
        ]);
    }

    /**
     * Un oggetto in cambio di un articolo che costa al massimo quanto vale: niente resto.
     * Qui si controlla solo che abbia senso chiedere; tutto si ricontrolla all'approvazione.
     */
    public function barter(Character $character, User $requester, CharacterItem $given, MarketItem $wanted): PendingChange
    {
        if ($given->character_id !== $character->getKey()) {
            throw new InvalidArgumentException('Puoi barattare solo un oggetto del tuo zaino.');
        }

        if (! $wanted->isAvailable()) {
            throw new InvalidArgumentException("«{$wanted->name}» non è disponibile.");
        }

        if ($given->value_cp < 1 || $wanted->price_cp > $given->value_cp) {
            throw new InvalidArgumentException(
                "«{$given->name}» vale ".Coins::formatValue((int) $given->value_cp)
                .": non basta per «{$wanted->name}», che costa ".Coins::formatValue($wanted->price_cp).'.'
            );
        }

        $giàOfferto = $character->pendingChanges()->pending()
            ->where('type', PendingChangeType::Barter)
            ->get()
            ->contains(fn (PendingChange $c) => ($c->diff['give']['name'] ?? null) === $given->name);

        if ($giàOfferto) {
            throw new InvalidArgumentException("Hai già offerto «{$given->name}» in un baratto che aspetta una risposta.");
        }

        return $this->create($character, $requester, PendingChangeType::Barter, [
            'diff' => [
                'give' => [...Character::itemCopy($given)],
                'take' => ['market_item_id' => $wanted->getKey(), 'name' => $wanted->name, 'price_cp' => $wanted->price_cp],
            ],
            'summary' => "Baratto: {$given->name} per {$wanted->name}",
        ]);
    }

    /**
     * Il Mago copia nel libro un incantesimo trovato su una pergamena. Costo e
     * regole stanno in config/dnd/rules.php; le monete si pagano all'approvazione.
     */
    public function scrollSpell(Character $character, User $requester, string $spell, ?int $itemId, ?GameSession $session = null): PendingChange
    {
        $class = self::scrollClass($character)
            ?? throw new InvalidArgumentException('La tua classe non copia incantesimi da una pergamena.');

        if (! in_array($spell, ClassRules::spellList($class), true)) {
            throw new InvalidArgumentException("«{$spell}» non è nella lista di un {$class}.");
        }

        $level = ClassRules::spellLevel($spell);

        if ($level === 0) {
            throw new InvalidArgumentException('I trucchetti non si copiano nel libro.');
        }

        if ($level > $character->spellSlots()->maxSpellLevel()) {
            throw new InvalidArgumentException("«{$spell}» è di livello {$level}: non hai ancora slot di quel livello.");
        }

        if ($character->spells()->where('name', $spell)->exists()) {
            throw new InvalidArgumentException("«{$spell}» è già nel tuo libro.");
        }

        $cost = self::scrollCost($level);

        if ($character->purseValue() < $cost) {
            throw new InvalidArgumentException('Copiarlo costa '.Coins::formatValue($cost).': non bastano le monete.');
        }

        $item = null;

        if (config('dnd.rules.scroll_copy.consumes_scroll')) {
            $item = $character->items()->whereKey($itemId)->first()
                ?? throw new InvalidArgumentException('Scegli la pergamena dallo zaino: la copia la consuma.');
        }

        return $this->create($character, $requester, PendingChangeType::ScrollSpell, [
            'diff' => ['scroll' => [
                'spell' => $spell,
                'spell_level' => $level,
                'cost_cp' => $cost,
                'item_id' => $item?->getKey(),
                'item_name' => $item?->name,
            ]],
            'summary' => "Copia «{$spell}» (liv. {$level}) nel libro: ".Coins::formatValue($cost)
                .($item ? ", consuma «{$item->name}»." : '.'),
            'game_session_id' => $session?->getKey(),
        ]);
    }

    /** La classe con cui si copia, se il personaggio ne ha una. */
    public static function scrollClass(Character $character): ?string
    {
        return collect($character->classLevels())
            ->keys()
            ->first(fn (string $class) => ClassRules::copiesScrolls($class));
    }

    public static function scrollCost(int $spellLevel): int
    {
        return $spellLevel * (int) config('dnd.rules.scroll_copy.gp_per_level') * 100;
    }

    /** @param array<string,mixed> $attributes */
    private function create(Character $character, User $requester, PendingChangeType $type, array $attributes): PendingChange
    {
        $change = PendingChange::create([
            'character_id' => $character->getKey(),
            'requested_by' => $requester->getKey(),
            'type' => $type,
            // Serve ad accorgersi se la scheda cambia mentre la richiesta aspetta.
            'base_updated_at' => $character->updated_at,
            ...$attributes,
        ]);

        app(AnnounceForApproval::class)->handle(new ChangeAwaitingApproval($change), $requester, session: $change->session);

        return $change;
    }

    private function unchanged(Character $character, string $field, mixed $value): bool
    {
        $current = $character->getAttribute($field);

        // Gli array si confrontano per contenuto: l'ordine diverso non è una modifica.
        if (is_array($current) && is_array($value)) {
            ksort($current);
            ksort($value);

            return $current === $value;
        }

        return $current == $value;
    }
}
