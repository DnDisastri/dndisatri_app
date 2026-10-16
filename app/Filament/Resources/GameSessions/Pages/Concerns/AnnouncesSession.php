<?php

namespace App\Filament\Resources\GameSessions\Pages\Concerns;

use App\Actions\AnnounceToPlayers;
use App\Notifications\GameSessionScheduled;

trait AnnouncesSession
{
    // Solo da pubblicata e per una sessione futura.
    protected function announceIfPublished(): void
    {
        if ($this->record->isDraft()) {
            return;
        }

        $annuncio = app(AnnounceToPlayers::class);

        if ($this->record->isUpcoming() && ($this->data['notify_players'] ?? true)) {
            $annuncio->handle($this->record, new GameSessionScheduled($this->record), auth()->user());
        } else {
            $annuncio->skip($this->record);
        }
    }
}
