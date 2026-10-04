<?php

namespace App\Filament\Resources\MarketItems\Tables;

use App\Domain\Dnd\Coins;
use App\Models\MarketItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MarketItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn (MarketItem $record) => $record->category)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_cp')
                    ->label('Prezzo')
                    ->formatStateUsing(fn (int $state) => Coins::formatValue($state))
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Scorte')
                    ->state(fn (MarketItem $record) => $record->is_unlimited ? '∞' : $record->stock)
                    ->badge()
                    ->color(fn (MarketItem $record) => $record->isAvailable() ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(fn () => MarketItem::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),

                TernaryFilter::make('disponibili')
                    ->label('Solo disponibili')
                    ->queries(
                        true: fn ($query) => $query->available(),
                        false: fn ($query) => $query->where('is_unlimited', false)->where('stock', '<=', 0),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Negozio vuoto')
            ->emptyStateDescription('Aggiungi il primo articolo al catalogo della gilda.');
    }
}
