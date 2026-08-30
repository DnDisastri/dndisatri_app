<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Le quattro azioni che un giocatore sotto richiamo non compie da solo (D13).
 *
 * L'acquisto dal negozio della gilda **non c'è**, ed è una scelta: i prezzi li
 * fissano gli admin e le scorte sono comuni, quindi non c'è nessuno da
 * truffare. Metterlo sotto controllo darebbe lavoro a chi approva senza
 * proteggere nessuno.
 */
enum SupervisedActionType: string
{
    case TradeProposal = 'trade_proposal';
    case TradeAcceptance = 'trade_acceptance';
    case ListingCreation = 'listing_creation';
    case ListingPurchase = 'listing_purchase';

    public function label(): string
    {
        return match ($this) {
            self::TradeProposal => 'Proposta di scambio',
            self::TradeAcceptance => 'Accettazione di uno scambio',
            self::ListingCreation => 'Messa in vendita',
            self::ListingPurchase => 'Acquisto da un annuncio',
        };
    }
}
