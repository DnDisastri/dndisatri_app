<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\Subrace;
use Illuminate\Support\Collection;

/**
 * Il catalogo delle sottorazze, letto una volta per richiesta.
 *
 * Come quello delle sottoclassi sta nel contenitore e non in una proprietà
 * statica: il wizard lo interroga a ogni passo mentre disegna la stessa
 * pagina, e fra un test e l'altro deve azzerarsi da solo.
 */
final class SubraceCatalogue
{
    /** @var array<string,Collection<int,Subrace>> */
    private array $perRazza = [];

    /** @return Collection<int,Subrace> */
    public function of(?string $race): Collection
    {
        if ($race === null || $race === '') {
            return collect();
        }

        return $this->perRazza[$race] ??= Subrace::ofRace($race)->ordered()->get();
    }

    public function find(?string $race, ?string $name): ?Subrace
    {
        if ($name === null || $name === '') {
            return null;
        }

        return $this->of($race)->firstWhere('name', $name);
    }

    /** Se la razza ne ha, sceglierne una non è facoltativo. */
    public function required(?string $race): bool
    {
        return $this->of($race)->isNotEmpty();
    }
}
