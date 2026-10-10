<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\Concerns\AnnouncesEvent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    use AnnouncesEvent;

    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // `players_notified_at` impedisce un secondo avviso a ogni modifica successiva.
    protected function afterSave(): void
    {
        $this->announceIfPublished();
    }
}
