<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\GameClass;
use Illuminate\Support\Collection;

/** Le classi, lette una volta per richiesta. Salvare una GameClass lo azzera. */
final class ClassCatalogue
{
    /** @var Collection<string,GameClass>|null */
    private ?Collection $perNome = null;

    /** @return Collection<string,GameClass> */
    public function all(): Collection
    {
        return $this->perNome ??= GameClass::ordered()->get()->keyBy('name');
    }

    public function find(?string $name): ?GameClass
    {
        return $name === null ? null : $this->all()->get($name);
    }
}
