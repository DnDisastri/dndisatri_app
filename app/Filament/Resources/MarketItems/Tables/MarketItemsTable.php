<?php

namespace App\Filament\Resources\MarketItems\Tables;

use App\Domain\Dnd\Coins;
use App\Enums\Icon;
use App\Models\CharacterItemEffect;
use App\Models\MarketItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                    ->description(fn (MarketItem $record) => collect([
                        $record->category,
                        self::tipo($record),
                        $record->effects ? 'effetto: '.collect($record->effects)->map(fn ($e) => CharacterItemEffect::describeCopy($e))->join(', ') : null,
                    ])->filter()->implode(' · '))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_cp')
                    ->label('Prezzo')
                    ->formatStateUsing(fn (int $state) => Coins::formatValue($state))
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Scorte')
                    ->state(fn (MarketItem $record) => match (true) {
                        $record->in_storage => 'in magazzino',
                        $record->is_unlimited => '∞',
                        default => $record->stock,
                    })
                    ->badge()
                    ->color(fn (MarketItem $record) => match (true) {
                        $record->in_storage => 'warning',
                        $record->isAvailable() => 'success',
                        default => 'danger',
                    }),
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
                self::putOnSaleAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Niente qui')
            ->emptyStateDescription('Gli articoli dell\'Emporio, e in magazzino gli oggetti arrivati dai baratti.');
    }

    /** Il prezzo parte dal valore che l'oggetto aveva nello zaino del giocatore. */
    private static function putOnSaleAction(): Action
    {
        return Action::make('mettiInVendita')
            ->label('Metti in vendita')
            ->icon(Icon::Shop)
            ->color('success')
            ->visible(fn (MarketItem $record) => auth()->user()->can('putOnSale', $record))
            ->authorize(fn (MarketItem $record) => auth()->user()->can('putOnSale', $record))
            ->modalHeading(fn (MarketItem $record) => "Mettere in vendita «{$record->name}»?")
            ->modalDescription('Da adesso compare nell\'Emporio e chiunque può comprarlo.')
            ->modalSubmitActionLabel('Metti in vendita')
            ->fillForm(fn (MarketItem $record) => ['prezzo' => $record->price_cp / 100])
            ->schema([
                TextInput::make('prezzo')
                    ->label('Prezzo')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(Coins::MAX / 100)
                    ->suffix('mo'),
            ])
            ->action(function (MarketItem $record, array $data) {
                $record->forceFill([
                    'price_cp' => (int) round((float) $data['prezzo'] * 100),
                    'in_storage' => false,
                ])->save();

                Notification::make()->success()->title("«{$record->name}» è in vendita.")->send();
            });
    }

    private static function tipo(MarketItem $record): ?string
    {
        $base = $record->base !== null && $record->base !== $record->name ? $record->base : null;
        $bonus = $record->magic_bonus > 0 ? "+{$record->magic_bonus}" : null;

        return ($base || $bonus) ? 'tipo: '.trim(($base ?? $record->name).' '.($bonus ?? '')) : null;
    }
}
