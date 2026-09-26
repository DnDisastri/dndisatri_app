<?php

namespace App\Filament\Resources\Subraces\Pages;

use App\Filament\Resources\Subraces\SubraceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubraces extends ListRecords
{
    protected static string $resource = SubraceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuova sottorazza'),
        ];
    }
}
