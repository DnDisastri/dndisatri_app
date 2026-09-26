<?php

namespace App\Filament\Resources\DmRequests;

use App\Enums\Icon;
use App\Filament\Resources\DmRequests\Pages\ListDmRequests;
use App\Filament\Resources\DmRequests\Pages\ViewDmRequest;
use App\Filament\Resources\DmRequests\Schemas\DmRequestInfolist;
use App\Filament\Resources\DmRequests\Tables\DmRequestsTable;
use App\Models\DmRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Le richieste per diventare dungeon master.
 *
 * Non si creano dal pannello: arrivano dai giocatori. Qui si leggono e si
 * decidono, e la decisione passa sempre da `ReviewDmRequest`, l'unico punto in
 * cui si assegna il ruolo.
 *
 * La visibilità la decide `DmRequestPolicy`, che Filament interroga da solo:
 * i DM non vedono nemmeno la voce di menu.
 */
class DmRequestResource extends Resource
{
    protected static ?string $model = DmRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::DmRequests;

    protected static string|UnitEnum|null $navigationGroup = 'Amministrazione';

    protected static ?string $modelLabel = 'richiesta DM';

    protected static ?string $pluralModelLabel = 'richieste DM';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 10;

    public static function infolist(Schema $schema): Schema
    {
        return DmRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DmRequestsTable::configure($table);
    }

    /** Il contatore rosso sulla voce di menu: quante aspettano una risposta. */
    public static function getNavigationBadge(): ?string
    {
        $pending = DmRequest::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDmRequests::route('/'),
            'view' => ViewDmRequest::route('/{record}'),
        ];
    }
}
