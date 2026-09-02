<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use InvalidArgumentException;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;

    private ?string $recapNuovo = null;

    /** @var list<array{user_id: int, character_id: int|null}> */
    private array $presenze = [];

    // L'autore della serata è chi la sta fissando. recap e presenze non sono
    // mass-assignable: si tengono da parte e si salvano dopo con le loro azioni.
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        $this->recapNuovo = $data['recap'] ?? null;
        $this->presenze = $data['presenze'] ?? [];
        unset($data['recap'], $data['presenze']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->recapNuovo)) {
            app(WriteRecap::class)->handle($this->record, auth()->user(), $this->recapNuovo);
        }

        $mappa = collect($this->presenze)
            ->filter(fn ($riga) => filled($riga['user_id'] ?? null))
            ->mapWithKeys(fn ($riga) => [(int) $riga['user_id'] => $riga['character_id'] ?? null]);

        if ($mappa->isEmpty()) {
            return;
        }

        try {
            app(RecordAttendance::class)->handle($this->record, $mappa);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
