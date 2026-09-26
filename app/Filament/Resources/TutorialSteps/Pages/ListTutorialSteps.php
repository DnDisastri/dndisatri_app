<?php

namespace App\Filament\Resources\TutorialSteps\Pages;

use App\Filament\Resources\TutorialSteps\TutorialStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTutorialSteps extends ListRecords
{
    protected static string $resource = TutorialStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
