<?php

namespace App\Filament\Resources\Maps;

use App\Enums\Icon;
use App\Filament\Resources\Maps\Pages\CreateMap;
use App\Filament\Resources\Maps\Pages\EditMap;
use App\Filament\Resources\Maps\Pages\ListMaps;
use App\Filament\Resources\Maps\Schemas\MapForm;
use App\Filament\Resources\Maps\Tables\MapsTable;
use App\Models\Map;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MapResource extends Resource
{
    protected static ?string $model = Map::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Maps;

    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

    protected static ?string $modelLabel = 'mappa';

    protected static ?string $pluralModelLabel = 'mappe';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return MapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MapsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaps::route('/'),
            'create' => CreateMap::route('/create'),
            'edit' => EditMap::route('/{record}/edit'),
        ];
    }
}
