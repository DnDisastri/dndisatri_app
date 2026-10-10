<?php

namespace App\Filament\Resources\Events\Pages\Concerns;

use App\Actions\AnnounceToPlayers;
use App\Notifications\EventPublished;

trait AnnouncesEvent
{
    // Una bozza non decide ancora niente: la scelta vale quando l'evento esce.
    protected function announceIfPublished(): void
    {
        if (! $this->record->isPublished()) {
            return;
        }

        $annuncio = app(AnnounceToPlayers::class);

        if ($this->record->hasEnded() || ! ($this->data['notify_players'] ?? true)) {
            $annuncio->skip($this->record);

            return;
        }

        $annuncio->handle($this->record, new EventPublished($this->record), auth()->user());
    }
}
