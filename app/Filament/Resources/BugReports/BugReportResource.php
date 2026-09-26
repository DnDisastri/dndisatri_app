<?php

namespace App\Filament\Resources\BugReports;

use App\Enums\Icon;
use App\Filament\Resources\BugReports\Pages\ListBugReports;
use App\Filament\Resources\BugReports\Pages\ViewBugReport;
use App\Filament\Resources\BugReports\Schemas\BugReportInfolist;
use App\Filament\Resources\BugReports\Tables\BugReportsTable;
use App\Models\BugReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * I problemi segnalati dai giocatori.
 *
 * Non si creano né si modificano dal pannello: nascono dal modulo dell'app e
 * qui si leggono e si chiudono. Solo admin.
 */
class BugReportResource extends Resource
{
    protected static ?string $model = BugReport::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::BugReports;

    protected static string|UnitEnum|null $navigationGroup = 'Amministrazione';

    protected static ?string $navigationLabel = 'Segnalazioni';

    protected static ?string $modelLabel = 'segnalazione';

    protected static ?string $pluralModelLabel = 'segnalazioni';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 40;

    /** Il nome di chi segnala si legge in tabella e nella scheda. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('reporter');
    }

    public static function infolist(Schema $schema): Schema
    {
        return BugReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BugReportsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Quante ne restano da guardare. */
    public static function getNavigationBadge(): ?string
    {
        $aperte = BugReport::query()->open()->count();

        return $aperte > 0 ? (string) $aperte : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBugReports::route('/'),
            'view' => ViewBugReport::route('/{record}'),
        ];
    }
}
