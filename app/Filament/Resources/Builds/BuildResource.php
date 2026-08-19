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
 * Sta in Redazione perché è contenuto da leggere, non un tavolo — ma a
 * differenza di news ed eventi **la vedono anche i DM**: una build è consiglio
 * di gioco, e chi conduce le serate è la persona che sa quale personaggio
 * funziona davvero.
 *
 * È l'unica voce di quel gruppo che un DM veda, e basta lei a far comparire la
 * sezione nel menù.
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
