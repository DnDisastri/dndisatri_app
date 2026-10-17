<?php

namespace App\Filament\Resources\MagicItems;

use App\Enums\Icon;
use App\Filament\Resources\MagicItems\Pages\CreateMagicItem;
use App\Filament\Resources\MagicItems\Pages\EditMagicItem;
use App\Filament\Resources\MagicItems\Pages\ListMagicItems;
use App\Filament\Resources\MagicItems\Schemas\MagicItemForm;
use App\Filament\Resources\MagicItems\Tables\MagicItemsTable;
use App\Models\MagicItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MagicItemResource extends Resource
{
    protected static ?string $model = MagicItem::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::MagicItem;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Oggetti magici';

    protected static ?string $modelLabel = 'oggetto magico';

    protected static ?string $pluralModelLabel = 'oggetti magici';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return MagicItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MagicItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMagicItems::route('/'),
            'create' => CreateMagicItem::route('/create'),
            'edit' => EditMagicItem::route('/{record}/edit'),
        ];
    }
}
