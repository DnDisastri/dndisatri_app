<?php

/*
 * Contenuti di partenza delle FAQ. L'ordine dell'elenco è l'ordine in pagina
 * (il seeder assegna `position` per riga); 'category' => null apre la pagina.
 */

return [
    [
        'category' => null,
        'question' => "Cos'è questa app?",
        'answer' => 'È lo spazio della gilda: qui trovi i tuoi personaggi, le storie a cui puoi partecipare, quando si gioca, il mercato e la cronaca del gruppo. La barra in basso porta alle cinque destinazioni principali; il menù in alto a destra al resto (Gilda, Build consigliate, le tue richieste, il profilo e questa guida).',
    ],
    [
        'category' => null,
        'question' => 'Da dove comincio?',
        'answer' => "Crea il tuo primo eroe dalla sezione Eroi, poi guarda le prossime sessioni, dove ti prenoti, e le Quest aperte. La Home riassume cosa c'è di nuovo ogni volta che entri.",
    ],

    [
        'category' => 'Eroi',
        'question' => 'Come creo un personaggio?',
        'answer' => 'Vai su Eroi (il cerchio al centro della barra) e scegli Crea il tuo personaggio. Una procedura guidata ti accompagna passo passo: razza, classe, caratteristiche ed equipaggiamento.',
    ],
    [
        'category' => 'Eroi',
        'question' => 'Cosa trovo nella scheda?',
        'answer' => 'Tutto il personaggio, diviso in sezioni: caratteristiche, abilità, attacchi, incantesimi, privilegi, equipaggiamento e la sua storia. Dalla scheda apri anche il suo registro, dove restano segnate le cose che gli succedono.',
    ],
    [
        'category' => 'Eroi',
        'question' => 'Posso cambiare la scheda dopo?',
        'answer' => 'Le modifiche importanti (salire di livello, aggiungere bottino, un oggetto magico, correggere la scheda) passano da una richiesta che approva un dungeon master. Le trovi come pulsanti sulla scheda, e lo stato in Le mie richieste, nel menù in alto.',
    ],

    [
        'category' => 'Quest',
        'question' => "Cos'è una quest?",
        'answer' => 'È una storia da giocare dentro una campagna. Nella pagina Quest vedi quelle aperte e la difficoltà. Se una ti piace tocca Mi interessa: quando il dungeon master la mette in una sessione ti arriverà un avviso, e per giocarla ti prenoti alla sessione.',
    ],
    [
        'category' => 'Quest',
        'question' => 'Come mi prenoto?',
        'answer' => "Chiedi un posto alla sessione, non alla quest. Dal Calendario nel menù spunta le sessioni che ti interessano (una al giorno: non ci si sdoppia fra due tavoli), scegli con quale eroe vieni e tocca Chiedo un posto. Se c'è posto per te ti arriverà un'email, e avrai 24 ore per confermarlo. In Le mie prenotazioni trovi tutte le tue richieste: lì confermi, rinunci, o resti fra le riserve se la sessione si riempie. Puoi tirarti indietro fino all'inizio.",
    ],

    [
        'category' => 'Sessioni',
        'question' => 'Dove vedo quando si gioca?',
        'answer' => "Nel Calendario, dal menù: le sessioni del mese giorno per giorno, con la campagna e l'ora. Da lì chiedi il posto, e aprendo una sessione vedi chi gioca e le quest che ci si giocano.",
    ],
    [
        'category' => 'Sessioni',
        'question' => "Cos'è il resoconto?",
        'answer' => "Dopo una sessione resta il racconto di cosa è successo e chi c'era, così chi non c'era recupera e chi c'era ricorda.",
    ],

    [
        'category' => 'Campagne',
        'question' => "Cos'è una campagna?",
        'answer' => 'Una storia lunga, fatta di più sessioni. Nella pagina Campagne vedi quelle in corso; aprendone una trovi di cosa parla, chi la conduce e chi ci gioca.',
    ],

    [
        'category' => 'Mercato',
        'question' => 'Cosa posso fare al mercato?',
        'answer' => "Tre cose, dalle linguette in alto: comprare dall'Emporio della gilda, mettere in vendita i tuoi oggetti negli Annunci, e proporre Scambi ad altri giocatori.",
    ],
    [
        'category' => 'Mercato',
        'question' => 'Come funziona uno scambio?',
        'answer' => "Proponi uno scambio all'altro giocatore, che può accettarlo o rifiutarlo. Finché è in sospeso lo ritrovi tra i tuoi scambi, e i dungeon master tengono d'occhio i passaggi importanti.",
    ],

    [
        'category' => 'Il resto',
        'question' => "Cos'è il Libro Mastro?",
        'answer' => "La memoria della gilda: le campagne concluse e cosa è successo nel tempo. È il posto dove si torna a guardare, mentre la Home racconta l'adesso.",
    ],
    [
        'category' => 'Il resto',
        'question' => 'Eventi e News, che differenza c\'è?',
        'answer' => 'Gli Eventi sono cose che succederanno (una data da segnare); le News sono gli annunci della gilda, dal più recente. A entrambi puoi lasciare una reazione.',
    ],
    [
        'category' => 'Il resto',
        'question' => 'Dove trovo notifiche, profilo e Gilda?',
        'answer' => 'Le notifiche sono la campanella in alto. Dal menù accanto apri il tuo profilo (dove cambi anche la password), la Gilda con i suoi eroi e i caduti, e questa guida.',
    ],
];
