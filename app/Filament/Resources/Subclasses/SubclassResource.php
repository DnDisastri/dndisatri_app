<?php

namespace App\Filament\Resources\Subclasses;

use App\Enums\Icon;
use App\Filament\Resources\Subclasses\Pages\CreateSubclass;
use App\Filament\Resources\Subclasses\Pages\EditSubclass;
use App\Filament\Resources\Subclasses\Pages\ListSubclasses;
use App\Filament\Resources\Subclasses\Schemas\SubclassForm;
use App\Filament\Resources\Subclasses\Tables\SubclassesTable;
use App\Models\Subclass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Le sottoclassi che i giocatori possono scegliere.
 *
 * Stavano in un file di configurazione: il gruppo può aggiungerne di proprie
 * senza toccare il codice, e la scelta resta al livello che il manuale dice.
 */
class SubclassResource extends Resource
{
    protected static ?string $model = Subclass::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Talents;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Sottoclassi';

    protected static ?string $modelLabel = 'sottoclasse';

    protected static ?string $pluralModelLabel = 'sottoclassi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return SubclassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubclassesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubclasses::route('/'),
            'create' => CreateSubclass::route('/create'),
            'edit' => EditSubclass::route('/{record}/edit'),
        ];
    }
}
