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

                TextColumn::make('interested_count')
                    ->label('Interessati')
                    ->counts('interested'),

                TextColumn::make('session.played_at')
                    ->label('Sessione')
                    ->dateTime('j M, H:i')
                    ->placeholder('Non ancora')
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
