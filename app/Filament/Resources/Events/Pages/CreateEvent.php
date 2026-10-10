<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\Concerns\AnnouncesEvent;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use AnnouncesEvent;

    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->announceIfPublished();
    }
}
