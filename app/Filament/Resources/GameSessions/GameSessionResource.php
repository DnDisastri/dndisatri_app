<?php

namespace App\Filament\Resources\GameSessions;

use App\Enums\Icon;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Schemas\GameSessionForm;
use App\Filament\Resources\GameSessions\Tables\GameSessionsTable;
use App\Models\GameSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class GameSessionResource extends Resource
{
    protected static ?string $model = GameSession::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Sessions;

    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

    protected static ?string $modelLabel = 'sessione';

    protected static ?string $pluralModelLabel = 'sessioni';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return GameSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameSessionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameSessions::route('/'),
            'create' => CreateGameSession::route('/create'),
            'edit' => EditGameSession::route('/{record}/edit'),
        ];
    }
}
