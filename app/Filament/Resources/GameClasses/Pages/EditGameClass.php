<?php

namespace App\Filament\Resources\GameClasses\Pages;

use App\Filament\Resources\GameClasses\GameClassResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGameClass extends EditRecord
{
    protected static string $resource = GameClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
