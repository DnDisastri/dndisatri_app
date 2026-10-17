<?php

namespace App\Filament\Resources\ClassFeatures;

use App\Enums\Icon;
use App\Filament\Resources\ClassFeatures\Pages\CreateClassFeature;
use App\Filament\Resources\ClassFeatures\Pages\EditClassFeature;
use App\Filament\Resources\ClassFeatures\Pages\ListClassFeatures;
use App\Filament\Resources\ClassFeatures\Schemas\ClassFeatureForm;
use App\Filament\Resources\ClassFeatures\Tables\ClassFeaturesTable;
use App\Models\ClassFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ClassFeatureResource extends Resource
{
    protected static ?string $model = ClassFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Privileges;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Privilegi';

    protected static ?string $modelLabel = 'privilegio';

    protected static ?string $pluralModelLabel = 'privilegi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return ClassFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassFeaturesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassFeatures::route('/'),
            'create' => CreateClassFeature::route('/create'),
            'edit' => EditClassFeature::route('/{record}/edit'),
        ];
    }
}
