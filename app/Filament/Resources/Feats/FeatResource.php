<?php

namespace App\Filament\Resources\Feats;

use App\Enums\Icon;
use App\Filament\Resources\Feats\Pages\CreateFeat;
use App\Filament\Resources\Feats\Pages\EditFeat;
use App\Filament\Resources\Feats\Pages\ListFeats;
use App\Filament\Resources\Feats\Schemas\FeatForm;
use App\Filament\Resources\Feats\Tables\FeatsTable;
use App\Models\Feat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FeatResource extends Resource
{
    protected static ?string $model = Feat::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Talents;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Talenti';

    protected static ?string $modelLabel = 'talento';

    protected static ?string $pluralModelLabel = 'talenti';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return FeatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeats::route('/'),
            'create' => CreateFeat::route('/create'),
            'edit' => EditFeat::route('/{record}/edit'),
        ];
    }
}
