<?php

namespace App\Filament\Resources\TutorialSteps\Pages;

use App\Filament\Resources\TutorialSteps\TutorialStepResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTutorialStep extends EditRecord
{
    protected static string $resource = TutorialStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
