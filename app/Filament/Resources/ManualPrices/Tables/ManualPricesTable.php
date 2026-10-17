<?php

namespace App\Filament\Resources\ManualPrices\Tables;

use App\Domain\Dnd\Coins;
use App\Models\ManualPrice;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManualPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('category')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Voce')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_cp')
                    ->label('Prezzo')
                    ->formatStateUsing(fn (int $state) => Coins::formatValue($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(fn () => ManualPrice::query()->distinct()->orderBy('category')->pluck('category', 'category')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Listino vuoto')
            ->emptyStateDescription('Le voci dell\'SRD arrivano con la migrazione.');
    }
}
