<?php

namespace App\Filament\Resources\MarketItems;

use App\Enums\Icon;
use App\Filament\Resources\MarketItems\Pages\CreateMarketItem;
use App\Filament\Resources\MarketItems\Pages\EditMarketItem;
use App\Filament\Resources\MarketItems\Pages\ListMarketItems;
use App\Filament\Resources\MarketItems\Schemas\MarketItemForm;
use App\Filament\Resources\MarketItems\Tables\MarketItemsTable;
use App\Models\MarketItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MarketItemResource extends Resource
{
    protected static ?string $model = MarketItem::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Shop;

    protected static string|UnitEnum|null $navigationGroup = 'Amministrazione';

    protected static ?string $modelLabel = 'articolo';

    protected static ?string $pluralModelLabel = 'catalogo del negozio';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MarketItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketItemsTable::configure($table);
    }

    /**
     * Il pannello è un'altra cosa dal sito pubblico: l'elenco delle news lo
     * legge tutto il gruppo (`viewAny` nella policy è aperto), ma la sezione
     * di redazione è degli admin. Filament usa la stessa policy per entrambi,
     * quindi la distinzione va fatta qui.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketItems::route('/'),
            'create' => CreateMarketItem::route('/create'),
            'edit' => EditMarketItem::route('/{record}/edit'),
        ];
    }
}
