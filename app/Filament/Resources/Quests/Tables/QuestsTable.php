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
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable(),

                TextColumn::make('difficulty')
                    ->label('Difficoltà')
                    ->badge()
                    ->visibleFrom('md'),

                // Prenotati sui posti, e se è raggiunto il minimo per giocare.
                TextColumn::make('posti')
                    ->label('Prenotati')
                    ->state(fn (Quest $record) => $record->participantCount().' / '.$record->max_participants)
                    ->description(fn (Quest $record) => $record->hasMinimum()
                        ? 'minimo raggiunto'
                        : 'ne mancano '.$record->missingToMinimum().' al minimo')
                    ->color(fn (Quest $record) => $record->hasMinimum() ? 'success' : 'warning'),

                TextColumn::make('attesa')
                    ->label('In attesa')
                    ->state(fn (Quest $record) => $record->waiting()->count() ?: 'Vuoto')
                    ->visibleFrom('md'),

                TextColumn::make('night_confirmed_at')
                    ->label('Serata')
                    ->state(fn (Quest $record) => $record->isNightConfirmed() ? 'Si fa' : 'Da decidere')
                    ->badge()
                    ->color(fn (Quest $record) => $record->isNightConfirmed() ? 'success' : 'gray')
                    ->visibleFrom('md'),

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

    /** Apre la pagina pubblica dell'incarico. */
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
