<?php

namespace App\Filament\Resources\Quests\Pages;

use App\Filament\Resources\Quests\QuestResource;
use App\Filament\Support\Bozza;
use Filament\Resources\Pages\CreateRecord;

class CreateQuest extends CreateRecord
{
    protected static string $resource = QuestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Bozza::applica($data, (bool) ($this->data['bozza'] ?? false));
    }
}
