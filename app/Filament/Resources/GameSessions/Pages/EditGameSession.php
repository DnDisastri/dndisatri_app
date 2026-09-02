<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditGameSession extends EditRecord
{
    protected static string $resource = GameSessionResource::class;

    private bool $recapCambiato = false;

    private ?string $recapNuovo = null;

    /** @var list<array{user_id: int, character_id: int|null}>|null */
    private ?array $presenze = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Le presenze non sono un attributo: si leggono dalla relazione attendees,
    // col personaggio giocato nel pivot.
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['presenze'] = $this->record->attendees->map(fn (User $user) => [
            'user_id' => $user->id,
            'character_id' => $user->pivot->character_id,
        ])->all();

        return $data;
    }

    // recap e presenze non sono mass-assignable: li tolgo dai dati e li salvo
    // dopo con le rispettive azioni, che tengono in ordine firma e pivot.
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $nuovo = (string) ($data['recap'] ?? '');
        unset($data['recap']);

        if ($nuovo !== (string) $this->record->recap) {
            $this->recapCambiato = true;
            $this->recapNuovo = $nuovo;
        }

        $this->presenze = $data['presenze'] ?? [];
        unset($data['presenze']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->recapCambiato) {
            app(WriteRecap::class)->handle($this->record, auth()->user(), (string) $this->recapNuovo);
        }

        $this->salvaPresenze();
    }

    private function salvaPresenze(): void
    {
        if ($this->presenze === null) {
            return;
        }

        $mappa = collect($this->presenze)
            ->filter(fn ($riga) => filled($riga['user_id'] ?? null))
            ->mapWithKeys(fn ($riga) => [(int) $riga['user_id'] => $riga['character_id'] ?? null]);

        try {
            app(RecordAttendance::class)->handle($this->record, $mappa);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
