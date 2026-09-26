<?php

namespace App\Filament\Resources\Subclasses\Pages;

use App\Filament\Resources\Subclasses\SubclassResource;
use App\Models\Subclass;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubclass extends EditRecord
{
    protected static string $resource = SubclassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (Subclass $record) => $record->is_homebrew),
        ];
    }
}
