<?php

namespace App\Filament\Resources\DmRequests\Pages;

use App\Filament\Resources\DmRequests\DmRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDmRequests extends ListRecords
{
    protected static string $resource = DmRequestResource::class;

    /**
     * Niente pulsante «Nuova»: le richieste arrivano dai giocatori, non si
     * creano dal pannello.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
