<?php

use App\Enums\TutorialIllustration;

/*
 * Passi di partenza del tutorial; l'ordine è quello dei capitoli.
 * Nel testo: `**parola**` = grassetto, `[icona:nome]` = icona (valore di App\Enums\Icon).
 */

return [
    [
        'illustration' => TutorialIllustration::BottomBar,
        'title' => 'La barra in basso',
        'body' => "Cinque destinazioni, sempre a portata di pollice:\n"
            ."[icona:campaigns] **Campagne**: le storie lunghe del gruppo.\n"
            ."[icona:ledger] **Libro Mastro**: la memoria della gilda.\n"
            ."[icona:characters] **Eroi** (il cerchio rosso al centro): i tuoi personaggi.\n"
            ."[icona:market] **Mercato**: compra, vendi e scambia.\n"
            .'[icona:events] **Eventi**: cosa c\'è in programma.',
    ],
    [
        'illustration' => TutorialIllustration::Menu,
        'title' => 'In alto a destra',
        'body' => "La campanella sono le **notifiche**, il tasto accanto apre il menù:\n"
            ."[icona:guild] **Gilda**: i membri e gli eroi caduti.\n"
            ."[icona:builds] **Build consigliate**: idee per far crescere un eroe.\n"
            ."[icona:proposals] **Le mie richieste**: lo stato delle modifiche che hai chiesto.\n"
            ."[icona:profile] **Il mio profilo**: i tuoi dati e la password.\n"
            .'[icona:faq] **FAQs**: questa pagina.',
    ],
    [
        'illustration' => TutorialIllustration::Hero,
        'title' => 'Crea il tuo eroe',
        'body' => "Tocca il cerchio rosso al centro della barra e scegli **Nuovo eroe**: una "
            ."procedura guidata ti porta passo passo tra razza, classe, caratteristiche ed "
            ."equipaggiamento.\n\n"
            ."Dopo, dalla scheda vedi tutto e apri il suo registro. Le modifiche importanti "
            ."(**salire di livello**, **bottino**, un **oggetto magico**) le proponi con un "
            .'pulsante e le approva chi conduce.',
    ],
    [
        'illustration' => TutorialIllustration::Sheet,
        'title' => 'La scheda del personaggio',
        'body' => "Ogni eroe ha una scheda divisa in linguette, così hai sotto mano solo quello "
            ."che ti serve in quel momento:\n"
            ."**Turno**: cosa puoi fare al tuo turno (attacchi, azioni, trucchetti).\n"
            ."**Prove**: caratteristiche e abilità, per i tiri.\n"
            ."**Magia**: incantesimi e slot (solo per chi lancia).\n"
            ."**Zaino**: equipaggiamento, oggetti e oro.\n"
            ."**Storia**: chi è il tuo personaggio, privilegi e note.\n\n"
            ."I **punti ferita** stanno sempre in alto, su tutte le linguette: prendere danni "
            .'non ti costa un cambio di sezione.',
    ],
    [
        'illustration' => TutorialIllustration::Quest,
        'title' => 'Trova un incarico',
        'body' => "Gli **incarichi** sono le storie aperte a cui puoi partecipare. Apri quello "
            ."che ti interessa e usa **Prenotati** con uno dei tuoi eroi, se ci sono posti "
            ."liberi.\n\n"
            ."Puoi **ritirarti** finché la serata non è fissata. Quando chi conduce sceglie la "
            .'data, ricevi una notifica.',
    ],
    [
        'illustration' => TutorialIllustration::Market,
        'title' => 'Il mercato',
        'body' => "[icona:shop] **Emporio**: compri dal negozio della gilda.\n"
            ."[icona:listings] **Annunci**: metti in vendita i tuoi oggetti.\n"
            ."[icona:trades] **Scambi**: proponi uno scambio a un altro giocatore, che accetta "
            ."o rifiuta.\n\n"
            .'Tutto passa per l\'**oro** dei tuoi personaggi.',
    ],
    [
        'illustration' => TutorialIllustration::Closing,
        'title' => 'Si gioca!',
        'body' => 'Questo è l\'essenziale. Le risposte alle domande più comuni le trovi qui '
            .'sotto, nelle **FAQ**. Buona avventura.',
    ],
];
