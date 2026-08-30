<?php

namespace App\Filament\Resources\Warnings\Tables;

use App\Actions\Users\LiftWarning;
use App\Enums\Icon;
use App\Models\Warning;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            /*
             * `user.warnings` è caricato apposta: le due colonne dello storico
             * contano su quella collezione invece di interrogare il database
             * riga per riga. Senza, una tabella di venti richiami farebbe
             * quaranta query per dire due numeri.
             */
            ->modifyQueryUsing(fn ($query) => $query->with(['user.warnings', 'issuedBy', 'liftedBy']))
            // Gli aperti per primi, e fra quelli il più vecchio in cima: chi
            // aspetta da più tempo è quello di cui ci si è dimenticati.
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Giocatore')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Perché')
                    ->wrap()
                    ->limit(80),

                TextColumn::make('lifted_at')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === null ? 'Sotto controllo' : 'Tolto')
                    ->color(fn ($state) => $state === null ? 'danger' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Dato il')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('issuedBy.name')
                    ->label('Da')
                    ->toggleable(),

                // Quanto è durato, o quanto dura da quando è stato dato.
                TextColumn::make('durata')
                    ->label('Giorni')
                    ->state(fn (Warning $record) => $record->daysLasted()),

                /*
                 * I due numeri che il gruppo vuole poter vedere: quante volte
                 * è servito intervenire su una persona, e quanto tempo in
                 * tutto ha passato sotto controllo. Si ripetono su ogni riga
                 * dello stesso giocatore, ed è voluto: si legge una riga alla
                 * volta, non una colonna alla volta.
                 */
                TextColumn::make('storico_quanti')
                    ->label('Richiami in tutto')
                    ->state(fn (Warning $record) => $record->user?->warnings->count() ?? 0)
                    ->toggleable(),

                TextColumn::make('storico_giorni')
                    ->label('Giorni sotto controllo')
                    ->state(fn (Warning $record) => (int) ($record->user?->warnings
                        ->sum(fn (Warning $w) => $w->daysLasted()) ?? 0))
                    ->toggleable(),

                TextColumn::make('liftedBy.name')
                    ->label('Tolto da')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('lift_note')
                    ->label('Nota')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('attivo')
                    ->label('Solo quelli in corso')
                    ->placeholder('Tutti')
                    ->trueLabel('In corso')
                    ->falseLabel('Già tolti')
                    ->queries(
                        true: fn ($query) => $query->active(),
                        false: fn ($query) => $query->lifted(),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                self::liftAction(),
            ])
            // Niente azioni di gruppo: un richiamo si toglie guardando in
            // faccia il caso, non spuntando cinque caselle.
            ->toolbarActions([]);
    }

    /**
     * M23 — togliere un richiamo.
     *
     * Un pulsante e una nota. La riga **non si cancella**: si chiude, e resta
     * nello storico con la sua durata. È il punto del meccanismo — serve a
     * ricordare, non a punire per sempre — e per questo `WarningPolicy::delete`
     * risponde no a chiunque.
     */
    private static function liftAction(): Action
    {
        return Action::make('togli')
            ->label('Togli')
            ->icon(Icon::Approve)
            ->color('success')
            ->modalHeading('Togliere il richiamo?')
            ->modalDescription(fn (Warning $record) => "{$record->user->name} torna a scambiare e a "
                .'vendere senza passare da un\'approvazione. Il richiamo resta scritto nello storico.')
            ->modalSubmitActionLabel('Togli il richiamo')
            ->schema([
                Textarea::make('nota')
                    ->label('Nota (facoltativa)')
                    ->helperText('Perché lo togli. Resta accanto al richiamo.')
                    ->rows(2),
            ])
            ->visible(fn (Warning $record) => $record->isActive())
            ->authorize(fn (Warning $record) => auth()->user()->can('lift', $record))
            ->action(function (Warning $record, array $data) {
                app(LiftWarning::class)->handle($record, auth()->user(), $data['nota'] ?? null);

                Notification::make()
                    ->title("Richiamo tolto a {$record->user->name}.")
                    ->success()
                    ->send();
            });
    }
}
