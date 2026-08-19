<?php

namespace App\Filament\Resources\Quests;

use App\Enums\Icon;
use App\Filament\Resources\Quests\Pages\CreateQuest;
use App\Filament\Resources\Quests\Pages\EditQuest;
use App\Filament\Resources\Quests\Pages\ListQuests;
use App\Filament\Resources\Quests\Schemas\QuestForm;
use App\Filament\Resources\Quests\Tables\QuestsTable;
use App\Models\Quest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Quests;

    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

    protected static ?string $modelLabel = 'quest';

    protected static ?string $pluralModelLabel = 'quest';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return QuestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuests::route('/'),
            'create' => CreateQuest::route('/create'),
            'edit' => EditQuest::route('/{record}/edit'),
        ];
    }
}
