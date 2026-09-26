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
 * I richiami (M21, M22, M23).
 *
 * La logica c'era da un pezzo — darne uno, toglierlo, lo storico, il controllo
 * sulle azioni di mercato — e **non c'era nessuna pagina da cui premere**. Un
 * richiamo si poteva dare soltanto dal database, e chi lo prendeva sarebbe
 * rimasto sotto controllo per sempre, perché nessuno aveva il modo di
 * toglierglielo. Non era una funzione mancante: era un difetto.
 *
 * Sta nel pannello e non al tavolo perché non è roba da mezzo della serata: è
 * un provvedimento che si prende a mente fredda.
 *
 * Non c'è una pagina di dettaglio. Un richiamo è tre cose — chi, perché,
 * quando — e stanno tutte nella riga: aprirne una quarta pagina per rileggere
 * le stesse tre sarebbe un passaggio in più per niente.
 */
class WarningResource extends Resource
{
    protected static ?string $model = Warning::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Warnings;

    /*
     * Sotto «Tavoli» e non sotto «Amministrazione»: i richiami li danno e li
     * tolgono anche i DM, ed è una scelta esplicita del gruppo — è chi conduce
     * le serate ad aver bisogno di quel dato, non chi amministra gli account.
     * In «Amministrazione» un DM non guarda, perché quasi tutto lì dentro non
     * è affar suo.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Tavoli';

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
