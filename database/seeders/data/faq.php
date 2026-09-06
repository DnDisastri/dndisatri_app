<?php

/*
 * Contenuti di partenza delle FAQ. L'ordine dell'elenco è l'ordine in pagina
 * (il seeder assegna `position` per riga); 'category' => null apre la pagina.
 */

return [
    [
        'category' => null,
        'question' => "Cos'è questa app?",
        'answer' => "È lo spazio della gilda: qui trovi i tuoi personaggi, le storie a cui puoi partecipare, quando si gioca, il mercato e la cronaca del gruppo. La barra in basso porta alle cinque destinazioni principali; il menù in alto a destra al resto (Gilda, Build consigliate, le tue richieste, il profilo e questa guida).",
    ],
    [
        'category' => null,
        'question' => 'Da dove comincio?',
        'answer' => "Crea il tuo primo eroe dalla sezione Eroi, poi guarda gli Incarichi aperti: sono le storie a cui puoi prenotarti. La Home riassume cosa c'è di nuovo ogni volta che entri.",
    ],

    [
        'category' => 'Eroi',
        'question' => 'Come creo un personaggio?',
        'answer' => "Vai su Eroi (il cerchio al centro della barra) e scegli Nuovo eroe. Una procedura guidata ti accompagna passo passo: razza, classe, caratteristiche ed equipaggiamento.",
    ],
    [
        'category' => 'Eroi',
        'question' => 'Cosa trovo nella scheda?',
        'answer' => "Tutto il personaggio, diviso in sezioni: caratteristiche, abilità, attacchi, incantesimi, privilegi, equipaggiamento e la sua storia. Dalla scheda apri anche il suo registro, dove restano segnate le cose che gli succedono.",
    ],
    [
        'category' => 'Eroi',
        'question' => 'Posso cambiare la scheda dopo?',
        'answer' => "Le modifiche importanti (salire di livello, aggiungere bottino, un oggetto magico, correggere la scheda) passano da una richiesta che chi conduce approva. Le trovi come pulsanti sulla scheda, e lo stato in Le mie richieste, nel menù in alto.",
    ],

    [
        'category' => 'Incarichi',
        'question' => "Cos'è un incarico?",
        'answer' => "È una storia aperta a cui puoi partecipare, con un numero di posti. Nella pagina Incarichi vedi quelli disponibili, la difficoltà e quanti posti restano liberi.",
    ],
    [
        'category' => 'Incarichi',
        'question' => 'Come mi prenoto?',
        'answer' => "Apri l'incarico e usa Prenotati con uno dei tuoi eroi, se ci sono posti liberi. Puoi ritirarti finché la serata non viene fissata. Quando chi conduce chiama i partecipanti e sceglie la data, ricevi una notifica.",
    ],

    [
        'category' => 'Serate',
        'question' => 'Dove vedo quando si gioca?',
        'answer' => "Nella sezione Serate: le prossime in programma e quelle già giocate, ognuna legata alla sua campagna o al suo incarico.",
    ],
    [
        'category' => 'Serate',
        'question' => "Cos'è il resoconto?",
        'answer' => "Dopo una serata resta il racconto di cosa è successo e chi c'era, così chi non c'era recupera e chi c'era ricorda.",
    ],

    [
        'category' => 'Campagne',
        'question' => "Cos'è una campagna?",
        'answer' => "Una storia lunga, fatta di più serate. Nella pagina Campagne vedi quelle in corso; aprendone una trovi di cosa parla, chi la conduce e chi ci gioca.",
    ],

    [
        'category' => 'Mercato',
        'question' => 'Cosa posso fare al mercato?',
        'answer' => "Tre cose, dalle linguette in alto: comprare dall'Emporio della gilda, mettere in vendita i tuoi oggetti negli Annunci, e proporre Scambi ad altri giocatori.",
    ],
    [
        'category' => 'Mercato',
        'question' => 'Come funziona uno scambio?',
        'answer' => "Proponi uno scambio all'altro giocatore, che può accettarlo o rifiutarlo. Finché è in sospeso lo ritrovi tra i tuoi scambi, e chi conduce tiene d'occhio i passaggi importanti.",
    ],

    [
        'category' => 'Il resto',
        'question' => "Cos'è il Libro Mastro?",
        'answer' => "La memoria della gilda: le campagne concluse e cosa è successo nel tempo. È il posto dove si torna a guardare, mentre la Home racconta l'adesso.",
    ],
    [
        'category' => 'Il resto',
        'question' => 'Eventi e News, che differenza c\'è?',
        'answer' => "Gli Eventi sono cose che succederanno (una data da segnare); le News sono gli annunci della gilda, dal più recente. A entrambi puoi lasciare una reazione.",
    ],
    [
        'category' => 'Il resto',
        'question' => 'Dove trovo notifiche, profilo e Gilda?',
        'answer' => "Le notifiche sono la campanella in alto. Dal menù accanto apri il tuo profilo (dove cambi anche la password), la Gilda con i suoi eroi e i caduti, e questa guida.",
    ],
];
