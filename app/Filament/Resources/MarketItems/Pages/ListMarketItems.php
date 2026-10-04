<?php

namespace App\Filament\Resources\MarketItems\Pages;

use App\Filament\Resources\MarketItems\MarketItemResource;
use App\Models\MarketItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMarketItems extends ListRecords
{
    protected static string $resource = MarketItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'in-vendita' => Tab::make('In vendita')
                ->modifyQueryUsing(fn (Builder $query) => $query->onSale()),

            'magazzino' => Tab::make('In magazzino')
                ->badge(MarketItem::inStorage()->count() ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->inStorage()),
        ];
    }

    /** I DM vengono qui per il magazzino: il catalogo non lo toccano. */
    public function getDefaultActiveTab(): string|int|null
    {
        return auth()->user()?->isAdmin() ? 'in-vendita' : 'magazzino';
    }
}
