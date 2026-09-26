<?php

namespace App\Filament\Resources\SupervisedActions\Pages;

use App\Actions\Supervision\ApproveSupervisedAction;
use App\Actions\Supervision\RejectSupervisedAction;
use App\Enums\Icon;
use App\Filament\Resources\SupervisedActions\SupervisedActionResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;

/**
 * M25 — l'esame di un'azione.
 *
 * Il dettaglio e i due pulsanti. Chi decide non è chiunque: il conflitto
 * d'interessi qui è **più largo** che in bacheca — non basta che non sia una
 * richiesta sua, non ci deve essere nessun suo personaggio dentro l'operazione.
 * Chi vende dall'altro lato non può essere anche l'arbitro. La regola sta in
 * `SupervisedActionPolicy` e qui si interroga: i pulsanti non compaiono
 * proprio, invece di comparire e dare 403.
 */
class ViewSupervisedAction extends ViewRecord
{
    protected static string $resource = SupervisedActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->rejectAction(),
        ];
    }

    /**
     * Il via libera **rigioca l'intenzione attraverso l'azione vera**, non la
     * riapplica a mano: le regole del mercato stanno in un posto solo, e quel
     * posto le fa rispettare anche adesso.
     *
     * Il che vuol dire che può fallire. Fra la richiesta e la decisione il
     * mondo si muove — l'oggetto venduto, l'oro speso, l'annuncio ritirato — e
     * allora si dice cos'è successo invece di lasciare una schermata bianca.
     * La richiesta resta aperta: non è stata decisa, si è solo scoperto che
     * adesso non si può.
     */
    private function approveAction(): Action
    {
        return Action::make('approva')
            ->label('Dai il via libera')
            ->icon(Icon::Approve)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Lasciare che si faccia?')
            ->modalDescription(fn () => $this->record->summary
                ?? 'L\'operazione verrà eseguita adesso, come se il richiamo non ci fosse.')
            ->modalSubmitActionLabel('Dai il via libera')
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('approve', $this->record))
            ->action(function () {
                try {
                    app(ApproveSupervisedAction::class)->handle($this->record, auth()->user());
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title('Non si è potuto fare')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()->title('Fatto: l\'operazione è andata a buon fine.')->success()->send();

                $this->refreshFormData([]);
            });
    }

    /**
     * Rifiutando, **il motivo è obbligatorio**.
     *
     * Non è una formalità: qui il giocatore è già sotto richiamo, e un blocco
     * senza spiegazione, a chi si sente osservato, è il modo più rapido di
     * trasformare una misura di controllo in un sospetto. Il gruppo gioca
     * insieme il sabato sera.
     */
    private function rejectAction(): Action
    {
        return Action::make('rifiuta')
            ->label('Blocca')
            ->icon(Icon::Reject)
            ->color('danger')
            ->modalHeading('Bloccare questa operazione?')
            ->modalDescription('Non succede niente al mercato: l\'intenzione non era mai stata eseguita. '
                .'Il giocatore legge il motivo nelle sue notifiche.')
            ->modalSubmitActionLabel('Blocca')
            ->schema([
                Textarea::make('note')
                    ->label('Perché')
                    ->required()
                    ->rows(3)
                    ->helperText('Lo legge il giocatore. Senza, il blocco non si dà.'),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('reject', $this->record))
            ->action(function (array $data) {
                app(RejectSupervisedAction::class)->handle($this->record, auth()->user(), $data['note']);

                Notification::make()->title('Operazione bloccata, col motivo.')->send();

                $this->refreshFormData([]);
            });
    }
}
