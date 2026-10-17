<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\ClassFeature;
use Illuminate\Support\Collection;

/** I privilegi di classi e sottoclassi, letti una volta per richiesta. Salvarne uno lo azzera. */
final class FeatureCatalogue
{
    /** @var Collection<string,Collection<int,ClassFeature>>|null */
    private ?Collection $perProprietario = null;

    /** @return Collection<int,ClassFeature> */
    public function of(string $ownerType, string $owner): Collection
    {
        $this->perProprietario ??= ClassFeature::query()
            ->orderBy('level')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ClassFeature $f) => $f->owner_type.'|'.$f->owner);

        return $this->perProprietario->get($ownerType.'|'.$owner, collect());
    }

    public function has(string $ownerType, string $owner): bool
    {
        return $this->of($ownerType, $owner)->isNotEmpty();
    }
}
