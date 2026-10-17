<?php

namespace App\Filament\Resources\BaseItems;

use App\Enums\Icon;
use App\Filament\Resources\BaseItems\Pages\CreateBaseItem;
use App\Filament\Resources\BaseItems\Pages\EditBaseItem;
use App\Filament\Resources\BaseItems\Pages\ListBaseItems;
use App\Filament\Resources\BaseItems\Schemas\BaseItemForm;
use App\Filament\Resources\BaseItems\Tables\BaseItemsTable;
use App\Models\BaseItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BaseItemResource extends Resource
{
    protected static ?string $model = BaseItem::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Attacks;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Armi e armature';

    protected static ?string $modelLabel = 'oggetto base';

    protected static ?string $pluralModelLabel = 'armi e armature';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return BaseItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BaseItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBaseItems::route('/'),
            'create' => CreateBaseItem::route('/create'),
            'edit' => EditBaseItem::route('/{record}/edit'),
        ];
    }
}
