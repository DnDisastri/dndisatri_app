<?php

namespace App\Filament\Resources\Subraces;

use App\Enums\Icon;
use App\Filament\Resources\Subraces\Pages\CreateSubrace;
use App\Filament\Resources\Subraces\Pages\EditSubrace;
use App\Filament\Resources\Subraces\Pages\ListSubraces;
use App\Filament\Resources\Subraces\Schemas\SubraceForm;
use App\Filament\Resources\Subraces\Tables\SubracesTable;
use App\Models\Subrace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Sottorazze, discendenze draconiche ed etnie: tre nomi per la stessa scelta,
 * quella che viene subito dopo la razza.
 */
class SubraceResource extends Resource
{
    protected static ?string $model = Subrace::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Guild;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Sottorazze';

    protected static ?string $modelLabel = 'sottorazza';

    protected static ?string $pluralModelLabel = 'sottorazze';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return SubraceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubracesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubraces::route('/'),
            'create' => CreateSubrace::route('/create'),
            'edit' => EditSubrace::route('/{record}/edit'),
        ];
    }
}
