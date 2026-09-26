<?php

namespace App\Filament\Resources\Events\Pages;

use App\Actions\AnnounceToPlayers;
use App\Filament\Resources\Events\EventResource;
use App\Notifications\EventPublished;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Avvisa i giocatori quando una bozza viene pubblicata; `players_notified_at`
    // impedisce un secondo avviso a ogni modifica successiva.
    protected function afterSave(): void
    {
        if ($this->record->isPublished()) {
            app(AnnounceToPlayers::class)->handle($this->record, new EventPublished($this->record), auth()->user());
        }
    }
}
