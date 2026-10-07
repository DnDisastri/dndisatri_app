<?php

namespace App\Filament\Resources\Warnings\Pages;

use App\Actions\Users\IssueWarning;
use App\Enums\Icon;
use App\Filament\Resources\Warnings\WarningResource;
use App\Models\User;
use App\Models\Warning;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use RuntimeException;

/**
 * M21: chi è sotto richiamo e lo storico. In testa M22, per darne uno guardando
 * subito i precedenti.
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

    /** M22: dare un richiamo. Il motivo è obbligatorio, lo legge chi lo riceve. */
    private function issueAction(): Action
    {
        return Action::make('richiama')
            ->label('Dai un richiamo')
            ->icon(Icon::Warnings)
            ->color('danger')
            ->modalHeading('Dare un richiamo')
            ->modalDescription('Da questo momento i suoi scambi e le sue vendite passano '
                .'dall\'approvazione di un DM, finché il richiamo non viene tolto. '
                .'L\'Emporio resta libero.')
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
            ->authorize(fn () => auth()->user()->can('create', Warning::class))
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
     * Chi si può richiamare: non gli admin, non sé stessi, non chi lo è già.
     * Le regole vere stanno in `IssueWarning`.
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
            // Nome ed email nell'etichetta: la ricerca del Select filtra sul
            // testo, così si trova la persona per nome o per email.
            ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} · {$user->email}"])
            ->all();
    }
}
