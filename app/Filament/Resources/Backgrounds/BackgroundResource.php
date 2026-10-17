<?php

namespace App\Filament\Resources\Backgrounds;

use App\Enums\Icon;
use App\Filament\Resources\Backgrounds\Pages\CreateBackground;
use App\Filament\Resources\Backgrounds\Pages\EditBackground;
use App\Filament\Resources\Backgrounds\Pages\ListBackgrounds;
use App\Filament\Resources\Backgrounds\Schemas\BackgroundForm;
use App\Filament\Resources\Backgrounds\Tables\BackgroundsTable;
use App\Models\Background;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BackgroundResource extends Resource
{
    protected static ?string $model = Background::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Characters;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Background';

    protected static ?string $modelLabel = 'background';

    protected static ?string $pluralModelLabel = 'background';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return BackgroundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BackgroundsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackgrounds::route('/'),
            'create' => CreateBackground::route('/create'),
            'edit' => EditBackground::route('/{record}/edit'),
        ];
    }
}
