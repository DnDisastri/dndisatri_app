<?php

namespace App\Filament\Resources\AboutPages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AboutPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Aggiornato')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nessun contenuto');
    }
}
