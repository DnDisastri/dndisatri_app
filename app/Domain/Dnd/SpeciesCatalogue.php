<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\RulesEdition;
use App\Models\Species;
use Illuminate\Support\Collection;

/** Le specie di un'edizione, lette una volta per richiesta. Salvare una Species lo azzera. */
final class SpeciesCatalogue
{
    /** @var array<string,Collection<string,Species>> */
    private array $perEdizione = [];

    /** @return Collection<string,Species> */
    public function all(RulesEdition $edition = RulesEdition::Classic): Collection
    {
        return $this->perEdizione[$edition->value] ??= Species::where('rules_edition', $edition->value)->ordered()->get()->keyBy('name');
    }

    public function find(?string $name, RulesEdition $edition = RulesEdition::Classic): ?Species
    {
        return $name === null ? null : $this->all($edition)->get($name);
    }

    /** @return list<string> */
    public function names(RulesEdition $edition = RulesEdition::Classic): array
    {
        return $this->all($edition)->keys()->all();
    }
}
