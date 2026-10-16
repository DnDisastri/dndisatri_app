<?php

namespace App\Filament\Resources\DmRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DmRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            // L'ordine della pagina dei DM si decide trascinando le righe.
            ->reorderable('position')
            ->columns([
                TextColumn::make('category')
                    ->label('Sezione')
                    ->badge()
                    ->placeholder('Nessuna')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Regola')
                    ->searchable()
                    ->limit(70),

                TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessuna regola per i DM');
    }
}
