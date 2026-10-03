<?php

namespace App\Filament\Resources\PendingChanges\Pages;

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\RejectPendingChange;
use App\Enums\Icon;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;

class ViewPendingChange extends ViewRecord
{
    protected static string $resource = PendingChangeResource::class;

    public function getTitle(): string
    {
        return 'Richiesta '.lcfirst($this->record->type->label());
    }

    public function getBreadcrumb(): string
    {
        return $this->record->type->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->rejectAction(),
        ];
    }

    private function approveAction(): Action
    {
        return Action::make('approva')
            ->label('Approva')
            ->icon(Icon::Approve)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Applicare la modifica?')
            ->modalDescription(fn () => $this->record->isStale()
                ? 'Attenzione: la scheda è cambiata dopo questa proposta.'
                : 'La scheda verrà aggiornata e il movimento finirà nel Registro.')
            ->schema([
                Textarea::make('note')->label('Nota (facoltativa)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('approve', $this->record))
            ->action(function (array $data) {
                try {
                    app(ApprovePendingChange::class)->handle(
                        $this->record,
                        auth()->user(),
                        $data['note'] ?? null,
                    );
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Richiesta approvata.')->success()->send();

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
            ->modalDescription('Il personaggio non verrà toccato. Il giocatore potrà riproporla.')
            ->schema([
                Textarea::make('note')->label('Motivo (facoltativo)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('reject', $this->record))
            ->action(function (array $data) {
                app(RejectPendingChange::class)->handle(
                    $this->record,
                    auth()->user(),
                    $data['note'] ?? null,
                );

                Notification::make()->title('Richiesta rifiutata.')->send();

                $this->refreshFormData([]);
            });
    }
}
