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
 * Le azioni sotto vigilanza (M24, M25).
 *
 * La seconda bacheca, **separata da quella delle richieste** perché sono cose
 * diverse: lì si valuta se un personaggio può salire di livello, qui se una
 * compravendita è pulita. Tenerle insieme vorrebbe dire mescolare due mestieri
 * e due criteri in una fila sola.
 *
 * Come per i richiami, la logica c'era e la pagina no: le azioni venivano
 * create e messe in attesa, e **nessuno poteva approvarle o rifiutarle**. Un
 * giocatore sotto richiamo chiedeva di vendere e non succedeva niente, per
 * sempre.
 *
 * Qui c'è la pagina di dettaglio, al contrario dei richiami: un'azione di
 * mercato non si giudica dal riassunto di una riga — bisogna vedere cosa esce
 * e cosa entra, e da quale personaggio.
 */
class SupervisedActionResource extends Resource
{
    protected static ?string $model = SupervisedAction::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Supervision;

    /*
     * Accanto ai richiami, che è la loro causa: si finisce sotto vigilanza
     * perché si è preso un richiamo, e chi guarda l'una guarda spesso l'altra.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

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
