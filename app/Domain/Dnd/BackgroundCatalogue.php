<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\RulesEdition;
use App\Models\Background;
use Illuminate\Support\Collection;

/** I background di un'edizione, letti una volta per richiesta. Salvarne uno lo azzera. */
final class BackgroundCatalogue
{
    /** @var array<string,Collection<string,Background>> */
    private array $perEdizione = [];

    /** @return Collection<string,Background> */
    public function all(RulesEdition $edition = RulesEdition::Classic): Collection
    {
        return $this->perEdizione[$edition->value] ??= Background::where('rules_edition', $edition->value)->ordered()->get()->keyBy('name');
    }

    public function find(?string $name, RulesEdition $edition = RulesEdition::Classic): ?Background
    {
        return $name === null ? null : $this->all($edition)->get($name);
    }
}
