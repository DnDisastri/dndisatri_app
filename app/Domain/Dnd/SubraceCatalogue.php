<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\RulesEdition;
use App\Models\Subrace;
use Illuminate\Support\Collection;

/**
 * Il catalogo delle sottorazze, letto una volta per richiesta.
 *
 * Come quello delle sottoclassi sta nel contenitore e non in una proprietà
 * statica: il wizard lo interroga a ogni passo mentre disegna la stessa
 * pagina, e fra un test e l'altro deve azzerarsi da solo. Il 2024 ha le sue:
 * l'elfo 2024 ha i lignaggi, non le sottorazze 2014.
 */
final class SubraceCatalogue
{
    /** @var array<string,Collection<int,Subrace>> */
    private array $perRazza = [];

    /** @return Collection<int,Subrace> */
    public function of(?string $race, RulesEdition $edition = RulesEdition::Classic): Collection
    {
        if ($race === null || $race === '') {
            return collect();
        }

        return $this->perRazza[$edition->value.'|'.$race] ??= Subrace::ofRace($race)
            ->where('rules_edition', $edition->value)
            ->ordered()
            ->get();
    }

    public function find(?string $race, ?string $name, RulesEdition $edition = RulesEdition::Classic): ?Subrace
    {
        if ($name === null || $name === '') {
            return null;
        }

        return $this->of($race, $edition)->firstWhere('name', $name);
    }

    /** Se la razza ne ha, sceglierne una non è facoltativo. */
    public function required(?string $race, RulesEdition $edition = RulesEdition::Classic): bool
    {
        return $this->of($race, $edition)->isNotEmpty();
    }
}
