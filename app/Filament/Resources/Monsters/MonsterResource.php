<?php

namespace App\Filament\Resources\Monsters;

use App\Enums\Icon;
use App\Filament\Resources\Monsters\Pages\CreateMonster;
use App\Filament\Resources\Monsters\Pages\EditMonster;
use App\Filament\Resources\Monsters\Pages\ListMonsters;
use App\Filament\Resources\Monsters\Schemas\MonsterForm;
use App\Filament\Resources\Monsters\Tables\MonstersTable;
use App\Models\Campaign;
use App\Models\Monster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Il bestiario, in Gestione: lo scrive un DM e lo pesca il tracker (M38).
 * Solo DM e admin.
 */
class MonsterResource extends Resource
{
    protected static ?string $model = Monster::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Bestiary;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $modelLabel = 'mostro';

    protected static ?string $pluralModelLabel = 'bestiario';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    // Un DM vede i mostri pubblici e quelli delle campagne che masterizza; un
    // admin li vede tutti.
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user !== null && ! $user->isAdmin()) {
            $query->where(fn (Builder $q) => $q
                ->whereNull('campaign_id')
                ->orWhereIn('campaign_id', Campaign::query()->runBy($user)->select('id')));
        }

        return $query;
    }

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
