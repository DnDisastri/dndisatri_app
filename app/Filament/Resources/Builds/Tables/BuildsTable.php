<?php

namespace App\Filament\Resources\Builds\Tables;

use App\Models\Build;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BuildsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Nome')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Build $record) => $record->tag),

                TextColumn::make('class')
                    ->label('Classe')
                    ->badge()
                    ->searchable()
                    ->description(fn (Build $record) => $record->subclass),

                // La colonna che conta: dice quali build servono davvero a
                // qualcosa e quali sono ancora mezze scritte.
                TextColumn::make('completa')
                    ->label('Di 1°')
                    ->badge()
                    ->state(fn (Build $record) => $record->isComplete() ? 'Completa' : 'Da finire')
                    ->color(fn (Build $record) => $record->isComplete() ? 'success' : 'warning')
                    ->tooltip(fn (Build $record) => $record->isComplete()
                        ? 'Chi la usa trova tutto già scelto.'
                        : 'Mancano specie, background, punteggi o abilità: chi la usa dovrà sceglierli.'),

                TextColumn::make('published_at')
                    ->label('Pubblicata')
                    ->dateTime('d/m/Y')
                    ->placeholder('bozza')
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Scritta da')
                    ->placeholder('la gilda')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('class')
                    ->label('Classe')
                    ->options(fn () => Build::query()
                        ->distinct()
                        ->orderBy('class')
                        ->pluck('class', 'class')),

                TernaryFilter::make('published_at')
                    ->label('Pubblicazione')
                    ->placeholder('Tutte')
                    ->trueLabel('Pubblicate')
                    ->falseLabel('Bozze')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('published_at'),
                        false: fn ($query) => $query->whereNull('published_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nessuna build')
            ->emptyStateDescription('Le build consigliate aiutano chi non ha voglia di studiarsi il manuale.')
            ->modifyQueryUsing(fn ($query) => $query->with('createdBy'));
    }
}
