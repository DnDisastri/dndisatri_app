<?php

namespace App\Filament\Resources\TutorialSteps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TutorialStepsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            // L'ordine dei capitoli si decide trascinando le righe.
            ->reorderable('position')
            ->columns([
                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable(),

                TextColumn::make('illustration')
                    ->label('Illustrazione')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Pubblicato')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Pubblicazione')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo pubblicati')
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
            ->emptyStateHeading('Nessun passo nel tutorial');
    }
}
