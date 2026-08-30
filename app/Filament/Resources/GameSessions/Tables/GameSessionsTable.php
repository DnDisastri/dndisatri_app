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

                /*
                 * Le due colonne che dicono cosa manca da fare. Un recap non
                 * scritto e delle presenze non segnate sono lavoro in sospeso,
                 * e prima qui c'erano l'id numerico di chi aveva firmato e la
                 * data: due cose che non si guardano mai.
                 */
                TextColumn::make('resoconto')
                    ->label('Resoconto')
                    ->state(fn (GameSession $record) => $record->hasRecap() ? 'Scritto' : 'Manca')
                    ->description(fn (GameSession $record) => $record->recapWrittenBy?->name)
                    ->badge()
                    ->color(fn (GameSession $record) => $record->hasRecap() ? 'success' : 'warning'),

                TextColumn::make('presenze')
                    ->label('Presenti')
                    ->state(fn (GameSession $record) => $record->attendees()->count() ?: '—'),
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

    /**
     * La porta verso la pagina della serata.
     *
     * **Il resoconto e le presenze si scrivono lì** (M13, M14): si fanno a
     * fine partita, col telefono in mano, e il racconto va dove i giocatori lo
     * leggeranno. Qui resta il lavoro da scrivania — fissare la data, correggere
     * il numero, cercare una serata vecchia.
     */
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
