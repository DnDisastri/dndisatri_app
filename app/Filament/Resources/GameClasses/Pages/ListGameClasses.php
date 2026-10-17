<?php

namespace App\Filament\Resources\GameClasses\Pages;

use App\Filament\Resources\GameClasses\GameClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameClasses extends ListRecords
{
    protected static string $resource = GameClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuova classe'),
        ];
    }
}
