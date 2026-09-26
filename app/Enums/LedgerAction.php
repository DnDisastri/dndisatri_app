<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * I movimenti tracciati nel Registro. I valori riprendono quelli della vecchia
 * applicazione dove esistevano, così la migrazione dei dati è diretta.
 */
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

    /** Oro assegnato da un DM. */
    case DmGold = 'dm-gold';

    /** Richiesta approvata dalla bacheca. */
    case Approve = 'approve';

    /** Transazione annullata da un admin: il movimento che rimette a posto. */
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Buy => 'Acquisto',
            self::SellList => 'Messa in vendita',
            self::ListingCancelled => 'Annuncio annullato',
            self::ListingSold => 'Venduto',
            self::ListingBought => 'Comprato da un giocatore',
            self::Trade => 'Scambio',
            self::DmGold => 'Oro dal DM',
            self::Approve => 'Richiesta approvata',
            self::Reversal => 'Annullamento',
        };
    }
}
