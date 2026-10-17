<?php

namespace App\Filament\Resources\GameClasses;

use App\Enums\Icon;
use App\Filament\Resources\GameClasses\Pages\CreateGameClass;
use App\Filament\Resources\GameClasses\Pages\EditGameClass;
use App\Filament\Resources\GameClasses\Pages\ListGameClasses;
use App\Filament\Resources\GameClasses\Schemas\GameClassForm;
use App\Filament\Resources\GameClasses\Tables\GameClassesTable;
use App\Models\GameClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class GameClassResource extends Resource
{
    protected static ?string $model = GameClass::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Guild;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Classi';

    protected static ?string $modelLabel = 'classe';

    protected static ?string $pluralModelLabel = 'classi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return GameClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameClassesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameClasses::route('/'),
            'create' => CreateGameClass::route('/create'),
            'edit' => EditGameClass::route('/{record}/edit'),
        ];
    }
}
