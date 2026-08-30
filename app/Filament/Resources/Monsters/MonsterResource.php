<?php

namespace App\Filament\Resources\Monsters;

use App\Enums\Icon;
use App\Filament\Resources\Monsters\Pages\CreateMonster;
use App\Filament\Resources\Monsters\Pages\EditMonster;
use App\Filament\Resources\Monsters\Pages\ListMonsters;
use App\Filament\Resources\Monsters\Schemas\MonsterForm;
use App\Filament\Resources\Monsters\Tables\MonstersTable;
use App\Models\Monster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Il bestiario.
 *
 * Sta fra i **Tavoli** e non in Redazione: non è contenuto da leggere come una
 * build, è uno strumento per condurre — lo scrive un DM e lo pesca dal tracker
 * di combattimento (M38). La policy lo tiene a DM e admin; i giocatori non lo
 * vedono.
 */
class MonsterResource extends Resource
{
    protected static ?string $model = Monster::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Bestiary;

    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

    protected static ?string $modelLabel = 'mostro';

    protected static ?string $pluralModelLabel = 'bestiario';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return MonsterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonstersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonsters::route('/'),
            'create' => CreateMonster::route('/create'),
            'edit' => EditMonster::route('/{record}/edit'),
        ];
    }
}
