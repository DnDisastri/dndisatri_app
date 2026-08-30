<?php

namespace App\Filament\Resources\Builds\Pages;

use App\Filament\Resources\Builds\BuildResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBuild extends CreateRecord
{
    protected static string $resource = BuildResource::class;

    /**
     * L'autore è chi sta scrivendo, e non è un campo del modulo: da lì passa
     * anche il permesso di rimetterci mano dopo.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
