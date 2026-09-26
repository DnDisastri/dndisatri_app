<?php

namespace App\Filament\Resources\Faqs;

use App\Enums\Icon;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\Faqs\Schemas\FaqForm;
use App\Filament\Resources\Faqs\Tables\FaqsTable;
use App\Models\Faq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * La guida della gilda. Solo admin: lo impone `FaqPolicy`, che Filament
 * interroga da solo.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Faq;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $navigationLabel = 'Guida';

    protected static ?string $modelLabel = 'voce della guida';

    protected static ?string $pluralModelLabel = 'voci della guida';

    protected static ?string $recordTitleAttribute = 'question';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
    }

    /** `viewAny` della policy è aperto a tutti; il pannello resta ai soli admin. */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
