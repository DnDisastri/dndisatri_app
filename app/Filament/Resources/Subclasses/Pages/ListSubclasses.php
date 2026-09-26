<?php

namespace App\Filament\Resources\Subclasses\Pages;

use App\Filament\Resources\Subclasses\SubclassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubclasses extends ListRecords
{
    protected static string $resource = SubclassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuova sottoclasse'),
        ];
    }
}
