<?php

namespace App\Filament\Resources\Warnings;

use App\Enums\Icon;
use App\Filament\Resources\Warnings\Pages\ListWarnings;
use App\Filament\Resources\Warnings\Tables\WarningsTable;
use App\Models\Warning;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

/**
 * I richiami (M21, M22, M23): darli, toglierli, lo storico.
 * Niente pagina di dettaglio: chi, perché e quando stanno già nella riga.
 */
class WarningResource extends Resource
{
    protected static ?string $model = Warning::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Warnings;

    // In «Gestione»: i richiami li danno e li tolgono anche i DM.
    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $modelLabel = 'richiamo';

    protected static ?string $pluralModelLabel = 'richiami';

    protected static ?string $recordTitleAttribute = 'reason';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return WarningsTable::configure($table);
    }

    /** Quanti sono aperti adesso. Rosso: è gente che sta aspettando. */
    public static function getNavigationBadge(): ?string
    {
        $attivi = Warning::active()->count();

        return $attivi > 0 ? (string) $attivi : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarnings::route('/'),
        ];
    }
}
