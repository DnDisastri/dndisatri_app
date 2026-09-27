<?php

namespace App\Filament\Resources\BugReports\Pages;

use App\Actions\Support\CloseBugReport;
use App\Enums\BugReportStatus;
use App\Enums\Icon;
use App\Filament\Resources\BugReports\BugReportResource;
use App\Models\BugReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBugReport extends ViewRecord
{
    protected static string $resource = BugReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->presaInCaricoAction(),
            $this->chiudiAction(BugReportStatus::Fixed),
            $this->chiudiAction(BugReportStatus::NotABug),
        ];
    }

    /** Solo un cambio di stato: chi ha segnalato non va disturbato per questo. */
    private function presaInCaricoAction(): Action
    {
        return Action::make('presaInCarico')
            ->label('Ci sto lavorando')
            ->icon(Icon::Supervision)
            ->color('warning')
            ->visible(fn (BugReport $record) => $record->status === BugReportStatus::Open)
            ->action(function (BugReport $record) {
                $record->forceFill(['status' => BugReportStatus::InProgress])->save();

                Notification::make()->success()->title('Segnata come in lavorazione.')->send();
            });
    }

    /**
     * La chiusura avvisa chi aveva segnalato, ed è il punto di tutto: una
     * segnalazione senza risposta insegna a non segnalare più.
     */
    private function chiudiAction(BugReportStatus $esito): Action
    {
        $risolta = $esito === BugReportStatus::Fixed;

        return Action::make($risolta ? 'risolvi' : 'scarta')
            ->label($risolta ? 'Risolta' : 'Non è un errore')
            ->icon($risolta ? Icon::Approve : Icon::Reject)
            ->color($risolta ? 'success' : 'gray')
            ->requiresConfirmation()
            ->modalHeading($risolta ? 'Chiudere come risolta?' : 'Chiudere come non errore?')
            ->modalDescription(fn (BugReport $record) => ($record->reporter?->name ?? 'Chi ha segnalato')
                .' riceverà un avviso con quello che scrivi qui sotto.')
            ->schema([
                Textarea::make('answer')
                    ->label($risolta ? 'Cosa è stato fatto (facoltativo)' : 'Perché funziona così (facoltativo)')
                    ->rows(3),
            ])
            ->visible(fn (BugReport $record) => ! $record->isClosed())
            ->action(function (BugReport $record, array $data) use ($esito) {
                app(CloseBugReport::class)->handle(
                    $record,
                    auth()->user(),
                    $esito,
                    $data['answer'] ?? null,
                );

                Notification::make()->success()->title('Segnalazione chiusa.')->send();

                $this->refreshFormData([]);
            });
    }
}
