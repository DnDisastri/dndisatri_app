<?php

namespace App\Filament\Resources\ManualPrices;

use App\Enums\Icon;
use App\Filament\Resources\ManualPrices\Pages\CreateManualPrice;
use App\Filament\Resources\ManualPrices\Pages\EditManualPrice;
use App\Filament\Resources\ManualPrices\Pages\ListManualPrices;
use App\Filament\Resources\ManualPrices\Schemas\ManualPriceForm;
use App\Filament\Resources\ManualPrices\Tables\ManualPricesTable;
use App\Models\ManualPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ManualPriceResource extends Resource
{
    protected static ?string $model = ManualPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Manual;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Listino del Manuale';

    protected static ?string $modelLabel = 'voce del listino';

    protected static ?string $pluralModelLabel = 'voci del listino';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return ManualPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManualPricesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManualPrices::route('/'),
            'create' => CreateManualPrice::route('/create'),
            'edit' => EditManualPrice::route('/{record}/edit'),
        ];
    }
}
