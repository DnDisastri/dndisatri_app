<?php

declare(strict_types=1);

namespace App\Enums;

/** I valori stanno nel database: non si rinominano. */
enum LedgerAction: string
{
    /** Acquisto dal negozio della gilda. */
    case Buy = 'buy';

    /** Oggetto messo in vendita: esce dall'inventario ed entra in deposito. */
    case SellList = 'sell-list';

    /** Annuncio annullato: l'oggetto torna al venditore. */
    case ListingCancelled = 'listing-cancel';

    /** Annuncio venduto, lato venditore. */
    case ListingSold = 'listing-sold';

    /** Acquisto da un altro giocatore. */
    case ListingBought = 'listing-buy';

    /** Scambio diretto fra due giocatori. */
    case Trade = 'trade';

    /** Monete assegnate o tolte da un DM. */
    case DmGold = 'dm-gold';

    /** Richiesta approvata dalla bacheca. */
    case Approve = 'approve';

    /** Transazione annullata da un admin: il movimento che rimette a posto. */
    case Reversal = 'reversal';

    /** Monete cambiate dalla scheda: valore zero, cambiano solo le pile. */
    case Exchange = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::Buy => 'Acquisto',
            self::SellList => 'Messa in vendita',
            self::ListingCancelled => 'Annuncio annullato',
            self::ListingSold => 'Venduto',
            self::ListingBought => 'Comprato da un giocatore',
            self::Trade => 'Scambio',
            self::DmGold => 'Monete dal DM',
            self::Approve => 'Richiesta approvata',
            self::Reversal => 'Annullamento',
            self::Exchange => 'Cambio monete',
        };
    }
}
