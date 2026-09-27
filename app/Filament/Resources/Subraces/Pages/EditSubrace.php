<?php

namespace App\Filament\Resources\Subraces\Pages;

use App\Filament\Resources\Subraces\SubraceResource;
use App\Models\Subrace;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubrace extends EditRecord
{
    protected static string $resource = SubraceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (Subrace $record) => $record->is_homebrew),
        ];
    }
}
