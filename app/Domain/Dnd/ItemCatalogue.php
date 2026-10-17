<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\EquipmentSlot;
use App\Models\BaseItem;
use Illuminate\Support\Collection;

/**
 * Il catalogo di armi, armature e scudi, letto una volta per richiesta: CA e
 * attacchi lo interrogano per ogni oggetto dello zaino. Salvare un BaseItem
 * lo azzera.
 */
final class ItemCatalogue
{
    /** @var Collection<string,BaseItem>|null */
    private ?Collection $perNome = null;

    /** @return Collection<string,BaseItem> */
    public function all(): Collection
    {
        return $this->perNome ??= BaseItem::ordered()->get()->keyBy('name');
    }

    public function find(?string $name): ?BaseItem
    {
        return $name === null ? null : $this->all()->get($name);
    }

    public function findOf(EquipmentSlot $kind, ?string $name): ?BaseItem
    {
        $item = $this->find($name);

        return $item?->kind === $kind ? $item : null;
    }

    /** @return Collection<string,BaseItem> */
    public function of(EquipmentSlot $kind): Collection
    {
        return $this->all()->filter(fn (BaseItem $item) => $item->kind === $kind);
    }
}
