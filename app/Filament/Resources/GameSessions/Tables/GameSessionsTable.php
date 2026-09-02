<?php

namespace App\Filament\Resources\GameSessions\Tables;

use App\Enums\Icon;
use App\Models\GameSession;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('played_at', 'desc')
            // recapWrittenBy si legge in una description: va precaricato, o con
            // il lazy loading disattivato la tabella esplode appena c'è una serata.
            ->modifyQueryUsing(fn ($query) => $query->with(['campaign', 'recapWrittenBy']))
            ->columns([
                TextColumn::make('campaign.title')
                    ->label('Campagna')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serata')
                    ->label('Serata')
                    ->state(fn (GameSession $record) => $record->displayTitle())
                    ->searchable(['number', 'title']),

                TextColumn::make('played_at')
                    ->label('Quando')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),

                // Cosa manca da fare sulla serata: resoconto e presenze.
                TextColumn::make('resoconto')
                    ->label('Resoconto')
                    ->state(fn (GameSession $record) => $record->hasRecap() ? 'Scritto' : 'Manca')
                    ->description(fn (GameSession $record) => $record->recapWrittenBy?->name)
                    ->badge()
                    ->color(fn (GameSession $record) => $record->hasRecap() ? 'success' : 'warning'),

                TextColumn::make('presenze')
                    ->label('Presenti')
                    ->state(fn (GameSession $record) => $record->attendees()->count() ?: 'Vuoto'),
            ])
            ->filters([
                TernaryFilter::make('senzaResoconto')
                    ->label('Solo quelle senza resoconto')
                    ->queries(
                        true: fn ($query) => $query->whereNull('recap')->orWhere('recap', ''),
                        false: fn ($query) => $query->withRecap(),
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

    /** Apre la pagina pubblica della serata. */
    private static function openAction(): Action
    {
        return Action::make('apri')
            ->label('Apri')
            ->icon(Icon::GoTo)
            ->color('gray')
            ->url(fn (GameSession $record) => route('sessions.show', $record))
            ->openUrlInNewTab();
    }
}
