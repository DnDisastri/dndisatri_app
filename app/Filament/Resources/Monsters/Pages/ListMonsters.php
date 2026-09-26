<?php

namespace App\Filament\Resources\Monsters\Pages;

use App\Filament\Resources\Monsters\MonsterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMonsters extends ListRecords
{
    protected static string $resource = MonsterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
