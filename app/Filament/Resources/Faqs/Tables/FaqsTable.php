<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            // L'ordine della guida si decide trascinando le righe.
            ->reorderable('position')
            ->columns([
                TextColumn::make('category')
                    ->label('Sezione')
                    ->badge()
                    ->placeholder('Nessuna')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('question')
                    ->label('Domanda')
                    ->searchable()
                    ->limit(70),

                IconColumn::make('is_published')
                    ->label('Pubblicata')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Pubblicazione')
                    ->placeholder('Tutte')
                    ->trueLabel('Solo pubblicate')
                    ->falseLabel('Solo bozze'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessuna voce nella guida');
    }
}
