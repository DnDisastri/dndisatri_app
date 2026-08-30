<?php

namespace App\Filament\Resources\PendingChanges;

use App\Enums\Icon;
use App\Filament\Resources\PendingChanges\Pages\ListPendingChanges;
use App\Filament\Resources\PendingChanges\Pages\ViewPendingChange;
use App\Filament\Resources\PendingChanges\Schemas\PendingChangeInfolist;
use App\Filament\Resources\PendingChanges\Tables\PendingChangesTable;
use App\Models\PendingChange;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * La bacheca delle richieste: il pattern centrale del gioco.
 *
 * È condivisa fra tutti i DM e tutti gli admin (decisione D1): la chiude il
 * primo che arriva, e chi ha deciso resta scritto sulla richiesta.
 *
 * Non ci sono pagine di creazione né modifica: le richieste arrivano dai
 * giocatori, e le decisioni passano dalle azioni di dominio.
 */
class PendingChangeResource extends Resource
{
    protected static ?string $model = PendingChange::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Proposals;

    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

    protected static ?string $modelLabel = 'richiesta';

    protected static ?string $pluralModelLabel = 'bacheca';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 0;

    public static function infolist(Schema $schema): Schema
    {
        return PendingChangeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PendingChangesTable::configure($table);
    }

    /** Quante aspettano una risposta: è la prima cosa che guarda un DM. */
    public static function getNavigationBadge(): ?string
    {
        $pending = PendingChange::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isDm() || $user?->isAdmin());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPendingChanges::route('/'),
            'view' => ViewPendingChange::route('/{record}'),
        ];
    }
}
