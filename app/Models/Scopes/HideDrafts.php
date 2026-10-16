<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Le bozze di sessioni e quest le vedono solo DM e admin; per gli altri mancano
 * ovunque, link diretti compresi (404). Senza utente (code, comandi) restano
 * fuori; i job ricaricano i loro modelli senza scope.
 */
class HideDrafts implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $utente = auth()->user();

        if ($utente !== null && ($utente->isDm() || $utente->isAdmin())) {
            return;
        }

        $builder->whereNotNull($model->qualifyColumn('published_at'));
    }
}
