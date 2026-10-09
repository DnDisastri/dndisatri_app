<?php

namespace App\Filament\Resources\SupervisedActions;

use App\Enums\Icon;
use App\Filament\Resources\SupervisedActions\Pages\ListSupervisedActions;
use App\Filament\Resources\SupervisedActions\Pages\ViewSupervisedAction;
use App\Filament\Resources\SupervisedActions\Schemas\SupervisedActionInfolist;
use App\Filament\Resources\SupervisedActions\Tables\SupervisedActionsTable;
use App\Models\SupervisedAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Le azioni di mercato sotto vigilanza (M24, M25), separate dalle richieste
 * sulla scheda. Hanno una pagina di dettaglio: per giudicarle serve vedere
 * cosa esce, cosa entra e da quale personaggio.
 */
class SupervisedActionResource extends Resource
{
    protected static ?string $model = SupervisedAction::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Supervision;

    /*
     * Accanto ai richiami, che è la loro causa: si finisce sotto vigilanza
     * perché si è preso un richiamo, e chi guarda l'una guarda spesso l'altra.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $modelLabel = 'azione sotto vigilanza';

    protected static ?string $pluralModelLabel = 'azioni sotto vigilanza';

    protected static ?string $recordTitleAttribute = 'summary';

    protected static ?int $navigationSort = 6;

    public static function infolist(Schema $schema): Schema
    {
        return SupervisedActionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupervisedActionsTable::configure($table);
    }

    /**
     * Nel pannello ci entrano solo DM e admin, e la voce è per loro: un
     * giocatore le proprie le segue dal mercato, non da qui.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isDm() || $user->isAdmin());
    }

    /** Quante aspettano una risposta. È il numero che dice se c'è da lavorare. */
    public static function getNavigationBadge(): ?string
    {
        $inAttesa = SupervisedAction::pending()->count();

        return $inAttesa > 0 ? (string) $inAttesa : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisedActions::route('/'),
            'view' => ViewSupervisedAction::route('/{record}'),
        ];
    }
}
