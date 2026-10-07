<?php

namespace App\Filament\Resources\Builds;

use App\Enums\Icon;
use App\Filament\Resources\Builds\Pages\CreateBuild;
use App\Filament\Resources\Builds\Pages\EditBuild;
use App\Filament\Resources\Builds\Pages\ListBuilds;
use App\Filament\Resources\Builds\Schemas\BuildForm;
use App\Filament\Resources\Builds\Tables\BuildsTable;
use App\Models\Build;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Le build consigliate.
 *
 * In Redazione, ma a differenza di news ed eventi la vedono anche i DM: è
 * l'unica voce del gruppo che fa comparire la sezione nel loro menù.
 */
class BuildResource extends Resource
{
    protected static ?string $model = Build::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Builds;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $modelLabel = 'build';

    protected static ?string $pluralModelLabel = 'build consigliate';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return BuildForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BuildsTable::configure($table);
    }

    /**
     * L'elenco pubblico lo legge tutto il gruppo (`viewAny` nella policy è
     * aperto), la sezione del pannello no. Filament usa la stessa policy per
     * entrambi, quindi la distinzione va fatta qui.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isDm() || $user?->isAdmin());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuilds::route('/'),
            'create' => CreateBuild::route('/create'),
            'edit' => EditBuild::route('/{record}/edit'),
        ];
    }
}
