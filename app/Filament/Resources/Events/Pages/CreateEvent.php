<?php

namespace App\Filament\Resources\Events\Pages;

use App\Actions\AnnounceToPlayers;
use App\Filament\Resources\Events\EventResource;
use App\Notifications\EventPublished;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    // Solo per un evento già visibile: una bozza avviserebbe di qualcosa che non c'è.
    protected function afterCreate(): void
    {
        if ($this->record->isPublished()) {
            app(AnnounceToPlayers::class)->handle($this->record, new EventPublished($this->record), auth()->user());
        }
    }
}
