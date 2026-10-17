<?php

namespace App\Filament\Resources\BaseItems\Pages;

use App\Filament\Resources\BaseItems\BaseItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaseItem extends EditRecord
{
    protected static string $resource = BaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
