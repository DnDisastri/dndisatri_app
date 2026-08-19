<?php

namespace App\Filament\Resources\PendingChanges\Pages;

use App\Filament\Resources\PendingChanges\PendingChangeResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingChanges extends ListRecords
{
    protected static string $resource = PendingChangeResource::class;

    /** Le richieste arrivano dai giocatori: non si creano dal pannello. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
