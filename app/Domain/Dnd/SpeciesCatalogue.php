<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\Species;
use Illuminate\Support\Collection;

/** Le specie, lette una volta per richiesta. Salvare una Species lo azzera. */
final class SpeciesCatalogue
{
    /** @var Collection<string,Species>|null */
    private ?Collection $perNome = null;

    /** @return Collection<string,Species> */
    public function all(): Collection
    {
        return $this->perNome ??= Species::ordered()->get()->keyBy('name');
    }

    public function find(?string $name): ?Species
    {
        return $name === null ? null : $this->all()->get($name);
    }

    /** @return list<string> */
    public function names(): array
    {
        return $this->all()->keys()->all();
    }
}
