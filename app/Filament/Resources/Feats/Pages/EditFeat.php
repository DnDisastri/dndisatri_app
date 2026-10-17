<?php

namespace App\Filament\Resources\Feats\Pages;

use App\Filament\Resources\Feats\FeatResource;
use App\Models\Feat;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeat extends EditRecord
{
    protected static string $resource = FeatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (Feat $record) => $record->is_homebrew),
        ];
    }
}
