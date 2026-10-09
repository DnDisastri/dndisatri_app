<?php

namespace App\Filament\Resources\PageIntros;

use App\Enums\Icon;
use App\Filament\Resources\PageIntros\Pages\EditPageIntro;
use App\Filament\Resources\PageIntros\Pages\ListPageIntros;
use App\Filament\Resources\PageIntros\Schemas\PageIntroForm;
use App\Filament\Resources\PageIntros\Tables\PageIntrosTable;
use App\Models\PageIntro;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/** Le righe sotto il titolo delle pagine. Solo admin, come impone `PageIntroPolicy`. */
class PageIntroResource extends Resource
{
    protected static ?string $model = PageIntro::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Edit;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $navigationLabel = 'Introduzioni';

    protected static ?string $modelLabel = 'introduzione';

    protected static ?string $pluralModelLabel = 'introduzioni';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return PageIntroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageIntrosTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageIntros::route('/'),
            'edit' => EditPageIntro::route('/{record}/edit'),
        ];
    }
}
