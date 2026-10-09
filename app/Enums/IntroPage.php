<?php

namespace App\Enums;

use App\Models\PageIntro;

/**
 * Le pagine con un'introduzione che gli admin riscrivono dal pannello.
 * Il testo qui è quello di partenza: vale finché nessuno lo cambia.
 */
enum IntroPage: string
{
    case Prelogin = 'prelogin';
    case Campaigns = 'campaigns';
    case Quests = 'quests';
    case Events = 'events';
    case Sessions = 'sessions';
    case Builds = 'builds';
    case Shop = 'shop';
    case Listings = 'listings';
    case Trades = 'trades';
    case Ledger = 'ledger';
    case Chronicle = 'chronicle';
    case Fallen = 'fallen';
    case News = 'news';
    case Faq = 'faq';
    case Encounters = 'encounters';

    public function label(): string
    {
        return match ($this) {
            self::Prelogin => 'Presentazione',
            self::Campaigns => 'Campagne',
            self::Quests => 'Quest',
            self::Events => 'Eventi',
            self::Sessions => 'Sessioni',
            self::Builds => 'Build consigliate',
            self::Shop => 'Mercato: Emporio',
            self::Listings => 'Mercato: Bancarella',
            self::Trades => 'Mercato: Scambi',
            self::Ledger => 'Registro',
            self::Chronicle => 'Libro Mastro',
            self::Fallen => 'Gilda: caduti',
            self::News => 'News',
            self::Faq => 'FAQs',
            self::Encounters => 'Area Master: Combattimenti',
        };
    }

    public function default(): string
    {
        return match ($this) {
            self::Prelogin => "Il destino ha tirato i dadi per te.\nOra tocca a te decidere cosa farne.\nProsegui, se l'avventura ti chiama.",
            self::Campaigns => 'Mondi da esplorare, avventure da vivere e leggende da scrivere. Scegli la tua prossima campagna.',
            self::Quests => 'Scegli la tua prossima avventura e preparati a partire.',
            self::Events => 'Raduni, one-shot e sessioni speciali. Le sessioni di campagna stanno dentro la loro storia.',
            self::Sessions => 'Quando si gioca, campagna per campagna.',
            self::Builds => 'Personaggi di 1° già pensati, per partire senza studiarsi il manuale. Le sfogli sempre; per usarne una serve non avere già un personaggio.',
            self::Shop => 'Benvenuto all\'Emporio della gilda! Trova equipaggiamento, strumenti e oggetti utili per affrontare al meglio le tue prossime quest.',
            self::Listings => 'Fai affari con i tuoi compagni di gilda: esplora gli oggetti offerti in scambio e cogli le migliori occasioni per ottenere ciò che ti serve.',
            self::Trades => 'Gestisci le tue richieste di scambio, controlla le offerte ricevute e segui gli scambi in corso.',
            self::Ledger => 'Ogni movimento di tutti i personaggi: bottini, acquisti, vendite, scambi, cambi di monete e le monete date dai dungeon master. È da qui che si capisce dove è finito qualcosa.',
            self::Chronicle => 'La memoria del gruppo: le quest concluse, le sessioni giocate e chi non è tornato.',
            self::Fallen => '«In memoria di coloro che hanno dato tutto per la causa…»',
            self::News => 'Gli annunci della gilda, dal più recente.',
            self::Faq => "Come funziona l'app e cosa puoi fare, sezione per sezione. Tocca una domanda per aprire la risposta.",
            self::Encounters => 'Preparali prima, conducili in sessione. Li vedono solo i DM: i giocatori vedono i loro PF che cambiano.',
        };
    }

    public function text(): string
    {
        return PageIntro::bodies()[$this->value] ?? $this->default();
    }
}
