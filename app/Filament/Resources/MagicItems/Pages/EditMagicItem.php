<?php

namespace App\Filament\Resources\MagicItems\Pages;

use App\Filament\Resources\MagicItems\MagicItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMagicItem extends EditRecord
{
    protected static string $resource = MagicItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
