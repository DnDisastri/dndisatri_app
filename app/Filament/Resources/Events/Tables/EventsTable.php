<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\Icon;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            // createdBy si legge in una description: va precaricato, altrimenti
            // col lazy loading disattivato la tabella esplode appena c'è un evento.
            ->modifyQueryUsing(fn ($query) => $query->with('createdBy'))
            ->columns([
                TextColumn::make('title')
                    ->label('Titolo')
                    ->description(fn (Event $record) => $record->createdBy?->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Quando')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Luogo')
                    ->placeholder('Vuoto')
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('stato')
                    ->label('Stato')
                    ->state(fn (Event $record) => $record->isPublished() ? 'Pubblicato' : 'Bozza')
                    ->badge()
                    ->color(fn (Event $record) => $record->isPublished() ? 'success' : 'gray')
                    ->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('pubblicati')
                    ->label('Solo pubblicati')
                    ->queries(
                        true: fn ($query) => $query->published(),
                        false: fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '>', now()),
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
            ])
            ->emptyStateHeading('Nessun evento')
            ->emptyStateDescription('Crea il primo raduno, one-shot o serata speciale.');
    }

    private static function openAction(): Action
    {
        return Action::make('apri')
            ->label('Apri')
            ->icon(Icon::GoTo)
            ->color('gray')
            ->url(fn (Event $record) => route('events.show', $record))
            ->openUrlInNewTab();
    }
}
