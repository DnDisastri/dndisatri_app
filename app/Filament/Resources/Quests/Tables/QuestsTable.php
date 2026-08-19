<?php

namespace App\Filament\Resources\Quests\Tables;

use App\Enums\Icon;
use App\Models\Quest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('campaign.title')
                    ->label('Campagna')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable(),

                TextColumn::make('difficulty')
                    ->label('Difficoltà')
                    ->badge(),

                /*
                 * La colonna che serve davvero a chi conduce: quanti hanno
                 * chiesto di esserci rispetto ai posti, e se si arriva al
                 * minimo. È la domanda «stasera si gioca o no?».
                 */
                TextColumn::make('posti')
                    ->label('Prenotati')
                    ->state(fn (Quest $record) => $record->participantCount().' / '.$record->max_participants)
                    ->description(fn (Quest $record) => $record->hasMinimum()
                        ? 'minimo raggiunto'
                        : 'ne mancano '.$record->missingToMinimum().' al minimo')
                    ->color(fn (Quest $record) => $record->hasMinimum() ? 'success' : 'warning'),

                TextColumn::make('attesa')
                    ->label('In attesa')
                    ->state(fn (Quest $record) => $record->waiting()->count() ?: '—'),

                TextColumn::make('night_confirmed_at')
                    ->label('Serata')
                    ->state(fn (Quest $record) => $record->isNightConfirmed() ? 'Si fa' : 'Da decidere')
                    ->badge()
                    ->color(fn (Quest $record) => $record->isNightConfirmed() ? 'success' : 'gray'),

                TextColumn::make('esito')
                    ->label('Stato')
                    ->state(fn (Quest $record) => $record->outcome()->label())
                    ->badge()
                    ->color(fn (Quest $record) => $record->isActive() ? 'primary' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('attive')
                    ->label('Solo le attive')
                    ->queries(
                        true: fn ($query) => $query->active(),
                        false: fn ($query) => $query->archived(),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                self::openAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * La porta verso la pagina dell'incarico.
     *
     * **La serata si fa**, **chiama dall'attesa** e **concludi** stavano qui, e
     * sono andate su quella pagina (M9, M10). Sono gesti da fine serata, con il
     * telefono in mano: chiedere di accendere il computer e aprire il Pannello
     * per dire «si gioca» era il posto sbagliato. Tenerli in tutti e due i
     * posti sarebbe stato peggio — D20 dice mai due pagine per la stessa cosa.
     *
     * Qui resta il lavoro da scrivania: creare, correggere, cercare.
     */
    private static function openAction(): Action
    {
        return Action::make('apri')
            ->label('Apri')
            ->icon(Icon::GoTo)
            ->color('gray')
            ->url(fn (Quest $record) => route('quests.show', $record))
            ->openUrlInNewTab();
    }
}
