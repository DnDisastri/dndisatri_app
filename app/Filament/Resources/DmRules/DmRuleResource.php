<?php

namespace App\Filament\Resources\DmRules;

use App\Enums\Icon;
use App\Filament\Resources\DmRules\Pages\CreateDmRule;
use App\Filament\Resources\DmRules\Pages\EditDmRule;
use App\Filament\Resources\DmRules\Pages\ListDmRules;
use App\Filament\Resources\DmRules\Schemas\DmRuleForm;
use App\Filament\Resources\DmRules\Tables\DmRulesTable;
use App\Models\DmRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/** Le regole del DM. Solo admin: lo impone `DmRulePolicy`. */
class DmRuleResource extends Resource
{
    protected static ?string $model = DmRule::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::DmRules;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $navigationLabel = 'Regole del DM';

    protected static ?string $modelLabel = 'regola del DM';

    protected static ?string $pluralModelLabel = 'regole del DM';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DmRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DmRulesTable::configure($table);
    }

    /** I DM le leggono nell'Area Master; nel pannello solo gli admin, che le scrivono. */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDmRules::route('/'),
            'create' => CreateDmRule::route('/create'),
            'edit' => EditDmRule::route('/{record}/edit'),
        ];
    }
}
