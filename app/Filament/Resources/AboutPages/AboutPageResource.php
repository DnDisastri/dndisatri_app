<?php

namespace App\Filament\Resources\AboutPages;

use App\Enums\Icon;
use App\Filament\Resources\AboutPages\Pages\EditAboutPage;
use App\Filament\Resources\AboutPages\Pages\ListAboutPages;
use App\Filament\Resources\AboutPages\Schemas\AboutPageForm;
use App\Filament\Resources\AboutPages\Tables\AboutPagesTable;
use App\Models\AboutPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/** «Chi siamo»: contenuto unico. Solo admin, come impone `AboutPagePolicy`. */
class AboutPageResource extends Resource
{
    protected static ?string $model = AboutPage::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::General;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $navigationLabel = 'Chi siamo';

    protected static ?string $modelLabel = 'chi siamo';

    protected static ?string $pluralModelLabel = 'chi siamo';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AboutPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutPagesTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /** È un contenuto unico: non se ne creano altri. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAboutPages::route('/'),
            'edit' => EditAboutPage::route('/{record}/edit'),
        ];
    }
}
