<?php

namespace App\Filament\Resources\MagicItems\Pages;

use App\Filament\Resources\MagicItems\MagicItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMagicItems extends ListRecords
{
    protected static string $resource = MagicItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuovo oggetto magico'),
        ];
    }
}
