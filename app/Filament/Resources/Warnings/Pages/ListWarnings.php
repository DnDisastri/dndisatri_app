<?php

namespace App\Filament\Resources\Warnings\Pages;

use App\Actions\Users\IssueWarning;
use App\Enums\Icon;
use App\Filament\Resources\Warnings\WarningResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use RuntimeException;

/**
 * M21 — chi è sotto richiamo adesso, e lo storico di tutti.
 *
 * In testa c'è M22, il modulo per darne uno: sta qui e non in una pagina sua
 * perché prima di dare un richiamo si guarda se quella persona ne ha già
 * presi, e la risposta è la tabella che sta sotto.
 */
class ListWarnings extends ListRecords
{
    protected static string $resource = WarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->issueAction(),
        ];
    }

    /**
     * M22 — dare un richiamo.
     *
     * Il modulo dice **cosa comporta** invece di limitarsi a chiederlo: chi lo
     * dà deve sapere che sta mettendo quattro azioni di mercato sotto
     * approvazione, e chi lo prende leggerà il motivo nelle sue notifiche. Per
     * questo il motivo è obbligatorio: un richiamo senza motivo non si può né
     * contestare né togliere con cognizione di causa.
     */
    private function issueAction(): Action
    {
        return Action::make('richiama')
            ->label('Dai un richiamo')
            ->icon(Icon::Warnings)
            ->color('danger')
            ->modalHeading('Dare un richiamo')
            ->modalDescription('Da questo momento i suoi scambi e le sue vendite passano '
                .'dall\'approvazione di un dungeon master, finché il richiamo non viene tolto. '
                .'Il negozio della gilda resta libero.')
            ->modalSubmitActionLabel('Dai il richiamo')
            ->schema([
                Select::make('user_id')
                    ->label('A chi')
                    ->options(fn () => self::richiamabili())
                    ->searchable()
                    ->required()
                    ->helperText('Chi è già sotto richiamo non compare: uno alla volta.'),

                Textarea::make('reason')
                    ->label('Perché')
                    ->required()
                    ->rows(3)
                    ->helperText('Lo legge il giocatore nelle sue notifiche.'),
            ])
            ->authorize(fn () => auth()->user()->can('create', \App\Models\Warning::class))
            ->action(function (array $data) {
                $target = User::findOrFail($data['user_id']);

                try {
                    app(IssueWarning::class)->handle($target, auth()->user(), $data['reason']);
                } catch (RuntimeException $e) {
                    // Fra l'apertura del modulo e l'invio qualcun altro può
                    // aver richiamato la stessa persona: si dice cos'è
                    // successo invece di lasciare una schermata bianca.
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title("{$target->name} è sotto richiamo.")
                    ->body('Gliel\'abbiamo detto, col motivo che hai scritto.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Chi si può richiamare: i giocatori che non lo sono già.
     *
     * Fuori restano gli amministratori — non giocano, e `IssueWarning` li
     * rifiuta comunque — e sé stessi, che sarebbe una cosa buffa. Le regole
     * vere stanno nell'azione: qui si tolgono dall'elenco per non far scegliere
     * qualcosa che poi verrebbe rifiutato.
     *
     * @return array<int,string>
     */
    private static function richiamabili(): array
    {
        return User::query()
            ->whereDoesntHave('warnings', fn ($query) => $query->active())
            ->whereKeyNot(auth()->id())
            ->orderBy('name')
            ->get()
            ->reject(fn (User $user) => $user->isAdmin())
            ->pluck('name', 'id')
            ->all();
    }
}
