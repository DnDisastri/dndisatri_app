<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\Subclass;
use Illuminate\Support\Collection;

/**
 * Il catalogo delle sottoclassi, letto una volta per richiesta.
 *
 * Il modulo delle build e la scheda di passaggio di livello lo interrogano
 * più volte mentre disegnano la stessa pagina. Sta nel contenitore e non in
 * una proprietà statica di proposito: così si azzera da solo fra un test e
 * l'altro, invece di trascinarsi dietro le righe di quello prima.
 */
final class SubclassCatalogue
{
    /** @var array<string,Collection<int,Subclass>> */
    private array $perClasse = [];

    /** @return Collection<int,Subclass> */
    public function of(?string $class): Collection
    {
        if ($class === null) {
            return collect();
        }

        return $this->perClasse[$class] ??= Subclass::ofClass($class)->ordered()->get();
    }
}
