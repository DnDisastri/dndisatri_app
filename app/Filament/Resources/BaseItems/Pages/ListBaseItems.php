<?php

namespace App\Filament\Resources\BaseItems\Pages;

use App\Filament\Resources\BaseItems\BaseItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaseItems extends ListRecords
{
    protected static string $resource = BaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuovo oggetto'),
        ];
    }
}
