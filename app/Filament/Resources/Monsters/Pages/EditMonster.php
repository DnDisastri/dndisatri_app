<?php

namespace App\Filament\Resources\Monsters\Pages;

use App\Filament\Resources\Monsters\MonsterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMonster extends EditRecord
{
    protected static string $resource = MonsterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
