<?php

namespace App\Filament\Resources\SupervisedActions\Pages;

use App\Filament\Resources\SupervisedActions\SupervisedActionResource;
use Filament\Resources\Pages\ListRecords;

/**
 * M24 — la seconda bacheca.
 *
 * Non si crea niente da qui: le azioni arrivano dai giocatori sotto richiamo,
 * che chiedono di scambiare o di vendere e restano in attesa. Qui si leggono e
 * si decidono, una alla volta.
 */
class ListSupervisedActions extends ListRecords
{
    protected static string $resource = SupervisedActionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
