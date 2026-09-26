<?php

namespace App\Filament\Resources\DmRequests\Pages;

use App\Actions\Users\ReviewDmRequest;
use App\Enums\Icon;
use App\Enums\PendingChangeStatus;
use App\Filament\Resources\DmRequests\DmRequestResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDmRequest extends ViewRecord
{
    protected static string $resource = DmRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->rejectAction(),
        ];
    }

    /**
     * Approvare assegna il ruolo `dm`. Passa da `ReviewDmRequest` e non da un
     * `update()` perché quello è l'unico punto del sistema in cui un ruolo
     * viene assegnato.
     */
    private function approveAction(): Action
    {
        return Action::make('approva')
            ->label('Approva')
            ->icon(Icon::Approve)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Promuovere a dungeon master?')
            ->modalDescription(fn () => "{$this->record->user->name} potrà creare campagne e "
                .'approvare le richieste di tutti i giocatori.')
            ->schema([
                Textarea::make('note')->label('Nota (facoltativa)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('approve', $this->record))
            ->action(function (array $data) {
                app(ReviewDmRequest::class)->handle(
                    $this->record,
                    auth()->user(),
                    PendingChangeStatus::Approved,
                    $data['note'] ?? null,
                );

                Notification::make()
                    ->title("{$this->record->user->name} è ora un dungeon master.")
                    ->success()
                    ->send();

                $this->refreshFormData([]);
            });
    }

    private function rejectAction(): Action
    {
        return Action::make('rifiuta')
            ->label('Rifiuta')
            ->icon(Icon::Reject)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Rifiutare la richiesta?')
            ->modalDescription('Potrà chiederlo di nuovo più avanti.')
            ->schema([
                Textarea::make('note')->label('Motivo (facoltativo)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('reject', $this->record))
            ->action(function (array $data) {
                app(ReviewDmRequest::class)->handle(
                    $this->record,
                    auth()->user(),
                    PendingChangeStatus::Rejected,
                    $data['note'] ?? null,
                );

                Notification::make()->title('Richiesta rifiutata.')->send();

                $this->refreshFormData([]);
            });
    }
}
