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
            ."[icona:market] **Mercato**: compra, vendi, scambia e baratta.\n"
            .'[icona:events] **Eventi**: cosa c\'è in programma.',
    ],
    [
        'illustration' => TutorialIllustration::Menu,
        'title' => 'In alto a destra',
        'body' => "La campanella sono le **notifiche**, il tasto accanto apre il menù:\n"
            ."[icona:guild] **Gilda**: i membri e gli eroi caduti.\n"
            ."[icona:builds] **Build consigliate**: idee per far crescere un eroe.\n"
            ."[icona:proposals] **Le mie richieste**: lo stato delle modifiche che hai chiesto.\n"
            ."[icona:profile] **Il mio profilo**: i tuoi dati, la password e quali email ricevere.\n"
            ."[icona:faq] **FAQs**: questa pagina.\n"
            ."[icona:bug-reports] **Segnala un problema**: se qualcosa non funziona.\n\n"
            .'In fondo al menù scegli il **tema**: chiaro, scuro o come il telefono.',
    ],
    [
        'illustration' => TutorialIllustration::Hero,
        'title' => 'Crea il tuo eroe',
        'body' => 'Tocca il cerchio rosso al centro della barra e poi **Crea il tuo personaggio**: '
            .'una procedura guidata ti porta passo passo tra razza, classe, caratteristiche ed '
            ."equipaggiamento.\n\n"
            .'Le modifiche importanti (**salire di livello**, **bottino**, un **oggetto magico**) '
            .'le proponi dalla scheda e le approva un dungeon master. Nel bottino cerchi gli oggetti per nome: '
            .'catalogo, Emporio e oggetti già trovati da altri riempiono il resto da soli.',
    ],
    [
        'illustration' => TutorialIllustration::Sheet,
        'title' => 'La scheda del personaggio',
        'body' => 'Ogni eroe ha una scheda divisa in linguette, così hai sotto mano solo quello '
            ."che ti serve in quel momento:\n"
            ."**Turno**: cosa puoi fare al tuo turno (attacchi, azioni, trucchetti).\n"
            ."**Prove**: caratteristiche e abilità, per i tiri.\n"
            ."**Magia**: incantesimi e slot (solo per chi lancia).\n"
            .'**Zaino**: monete, oggetti ed equipaggiamento. Da qui **impugni** armi e scudi, '
            ."**indossi** l'armatura e vai in **sintonia** con gli oggetti magici (al massimo tre). Con **Nota** scrivi "
            ."da dove viene un oggetto o cosa ti ricorda.\n"
            ."**Storia**: chi è il tuo personaggio, privilegi e note.\n\n"
            .'I **punti ferita** stanno sempre in alto, su tutte le linguette. Le monete sono '
            .'quattro (mp, mo, ma, mr) e nello zaino le puoi cambiare fra loro.',
    ],
    [
        'illustration' => TutorialIllustration::Quest,
        'title' => 'Trova una quest',
        'body' => 'Si gioca nelle **sessioni**: il dungeon master ne annuncia una con i suoi posti, '
            .'e tu ti prenoti dalla sua pagina con uno dei tuoi eroi. Se i posti sono finiti entri in '
            ."**lista d'attesa**; puoi **tirarti indietro** fino all'inizio. Quando il dungeon master "
            ."conferma la sessione, ricevi una notifica.\n\n"
            .'Le **quest** sono le storie da giocare: le trovi nella Home e nelle campagne. '
            .'Su una quest tocca **Mi interessa**: quando il dungeon master la mette in una sessione, '
            .'ti arriva un avviso per prenotarti.',
    ],
    [
        'illustration' => TutorialIllustration::Market,
        'title' => 'Il mercato',
        'body' => '[icona:shop] **Emporio**: compri dalla gilda, oppure **baratti** '
            ."un tuo oggetto che valga almeno il prezzo (lo approva un dungeon master).\n"
            ."[icona:listings] **Annunci**: metti in vendita i tuoi oggetti agli altri giocatori.\n"
            .'[icona:trades] **Scambi**: proponi uno scambio a un altro giocatore, che accetta '
            ."o rifiuta. Con **Scambio** nello zaino metti un oggetto in vetrina: gli altri vedono cosa daresti.\n\n"
            .'Si paga con le **monete** dei tuoi personaggi, e il resto lo calcola l\'app.',
    ],
    [
        'illustration' => TutorialIllustration::Closing,
        'title' => 'Si gioca!',
        'body' => 'Questo è l\'essenziale. Le risposte alle domande più comuni le trovi qui '
            .'sotto, nelle **FAQ**. Buona avventura.',
    ],
];
