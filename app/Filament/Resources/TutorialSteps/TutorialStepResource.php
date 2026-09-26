<?php

namespace App\Filament\Resources\TutorialSteps;

use App\Enums\Icon;
use App\Filament\Resources\TutorialSteps\Pages\CreateTutorialStep;
use App\Filament\Resources\TutorialSteps\Pages\EditTutorialStep;
use App\Filament\Resources\TutorialSteps\Pages\ListTutorialSteps;
use App\Filament\Resources\TutorialSteps\Schemas\TutorialStepForm;
use App\Filament\Resources\TutorialSteps\Tables\TutorialStepsTable;
use App\Models\TutorialStep;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Il tutorial illustrato. Solo admin: lo impone `TutorialStepPolicy`, che
 * Filament interroga da solo.
 */
class TutorialStepResource extends Resource
{
    protected static ?string $model = TutorialStep::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Faq;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $navigationLabel = 'Tutorial';

    protected static ?string $modelLabel = 'passo del tutorial';

    protected static ?string $pluralModelLabel = 'passi del tutorial';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TutorialStepForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorialStepsTable::configure($table);
    }

    /** `viewAny` della policy è aperto a tutti; il pannello resta ai soli admin. */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutorialSteps::route('/'),
            'create' => CreateTutorialStep::route('/create'),
            'edit' => EditTutorialStep::route('/{record}/edit'),
        ];
    }
}
