<?php

namespace App\Filament\Resources\Quests\Pages;

use App\Filament\Resources\Quests\QuestResource;
use App\Filament\Support\Bozza;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuest extends EditRecord
{
    protected static string $resource = QuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Bozza::applica($data, (bool) ($this->data['bozza'] ?? false), $this->record);
    }
}
