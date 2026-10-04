<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Enums\EquipmentSlot;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\MarketItem;
use App\Models\PendingChange;
use Illuminate\Support\Collection;

/**
 * Gli oggetti fra cui cercare nel modulo del bottino: catalogo di combattimento,
 * negozio e oggetti già approvati in altri bottini (visibili a tutti, per scelta).
 * A parità di nome vince il negozio, poi il catalogo.
 */
final class LootSuggestions
{
    private const APPROVATI_LETTI = 200;

    /** @return list<array{name: string, category: ?string, value_cp: int, details: ?string, base: ?string, source: string}> */
    public function handle(): array
    {
        return $this->approved()
            ->merge($this->catalog())
            ->merge($this->shop())
            ->keyBy(fn (array $voce) => mb_strtolower($voce['name']))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function catalog(): Collection
    {
        return collect(EquipmentSlot::bases())->flatMap(
            fn (array $nomi, string $gruppo) => collect($nomi)->map(fn (string $nome) => $this->voce(
                name: $nome,
                category: $gruppo === 'Armi' ? 'Armi' : 'Armature',
                base: $nome,
                source: 'catalogo',
            ))->values()
        );
    }

    private function shop(): Collection
    {
        return MarketItem::onSale()
            ->orderBy('name')
            ->get(['name', 'base', 'category', 'price_cp', 'details'])
            ->map(fn (MarketItem $item) => $this->voce(
                name: $item->name,
                category: $item->category,
                valueCp: (int) $item->price_cp,
                details: $item->details,
                base: $item->base ?? (EquipmentSlot::isBase($item->name) ? $item->name : null),
                source: 'negozio',
            ));
    }

    private function approved(): Collection
    {
        return PendingChange::query()
            ->where('type', PendingChangeType::Loot)
            ->where('status', PendingChangeStatus::Approved)
            ->latest('id')
            ->limit(self::APPROVATI_LETTI)
            ->get(['grant_items'])
            ->pluck('grant_items')
            ->reverse()
            ->flatten(1)
            ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
            ->map(fn (array $item) => $this->voce(
                name: $item['name'],
                category: $item['category'] ?? null,
                valueCp: (int) ($item['value_cp'] ?? 0),
                details: $item['details'] ?? null,
                base: EquipmentSlot::isBase($item['base'] ?? null) ? $item['base'] : null,
                source: 'già trovato',
            ));
    }

    /** @return array{name: string, category: ?string, value_cp: int, details: ?string, base: ?string, source: string} */
    private function voce(
        string $name,
        ?string $category = null,
        int $valueCp = 0,
        ?string $details = null,
        ?string $base = null,
        string $source = '',
    ): array {
        return [
            'name' => $name,
            'category' => $category,
            'value_cp' => $valueCp,
            'details' => $details,
            'base' => $base,
            'source' => $source,
        ];
    }
}
