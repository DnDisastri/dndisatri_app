<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Actions\Approvals\AnnounceForApproval;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\ChangeAwaitingApproval;
use InvalidArgumentException;

/**
 * Le proposte semplici del giocatore: modifica della scheda, bottino di
 * sessione, oggetto magico trovato.
 *
 * Il passaggio di livello ha un'azione sua (`RequestLevelUp`) perché comporta
 * un calcolo; queste tre no.
 */
final class ProposeChange
{
    /**
     * Modifica della scheda.
     *
     * Si salva **solo quello che cambia davvero**: un modulo rimandato senza
     * toccare niente non deve produrre una richiesta vuota da esaminare, e il
     * DM deve vedere le due o tre cose modificate, non tutta la scheda.
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
     * Bottino di sessione.
     *
     * @param  list<array{name: string, qty?: int, category?: string, value?: int}>  $items
     */
    public function loot(Character $character, User $requester, int $gp = 0, array $items = [], ?string $note = null): PendingChange
    {
        if ($gp === 0 && $items === []) {
            throw new InvalidArgumentException('Un bottino vuoto non si registra.');
        }

        $parts = array_filter([
            $gp !== 0 ? "{$gp} mo" : null,
            $items !== [] ? collect($items)->map(fn ($i) => ($i['qty'] ?? 1)."× {$i['name']}")->join(', ') : null,
        ]);

        return $this->create($character, $requester, PendingChangeType::Loot, [
            'grant_gp' => $gp,
            'grant_items' => $items,
            'summary' => 'Bottino: '.implode(' e ', $parts).($note ? " — {$note}" : ''),
        ]);
    }

    /** Oggetto magico che altera una caratteristica. */
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

    /** @param array<string,mixed> $attributes */
    private function create(Character $character, User $requester, PendingChangeType $type, array $attributes): PendingChange
    {
        $change = PendingChange::create([
            'character_id' => $character->getKey(),
            'requested_by' => $requester->getKey(),
            'type' => $type,
            // Fotografa com'era la scheda: serve ad accorgersi se cambia
            // mentre la richiesta aspetta in bacheca.
            'base_updated_at' => $character->updated_at,
            ...$attributes,
        ]);

        app(AnnounceForApproval::class)->handle(new ChangeAwaitingApproval($change), $requester);

        return $change;
    }

    private function unchanged(Character $character, string $field, mixed $value): bool
    {
        $current = $character->getAttribute($field);

        // I campi JSON vanno confrontati per contenuto: due array uguali
        // scritti in ordine diverso non sono una modifica.
        if (is_array($current) && is_array($value)) {
            ksort($current);
            ksort($value);

            return $current === $value;
        }

        return $current == $value;
    }
}
