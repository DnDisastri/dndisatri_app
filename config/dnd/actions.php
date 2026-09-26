<?php

/*
 * Quello che può fare chiunque, nel proprio turno.
 *
 * Non è un privilegio di nessuna classe: è il regolamento. Sta qui perché è la
 * parte che al tavolo non sa nessuno — si impara che si può schivare, o
 * disingaggiare, dopo mesi che si gioca, di solito guardando qualcun altro
 * farlo. Un giocatore nuovo che apre la scheda sul telefono deve poterlo
 * leggere senza chiedere.
 *
 * L'ordine non è alfabetico ed è voluto: prima quelle che si usano davvero.
 *
 * Attribuzione: le regole vengono dal System Reference Document 5.1 di Wizards
 * of the Coast LLC — la dichiarazione completa è in testa a features.php, che
 * viene dalla stessa fonte.
 */

return [
    [
        'nome' => 'Attaccare',
        'costo' => 'azione',
        'testo' => 'Un attacco con un\'arma, in mischia o a distanza. Con «Attacco extra» ne fai più di uno con la stessa azione.',
    ],
    [
        'nome' => 'Lanciare un incantesimo',
        'costo' => 'azione',
        'testo' => 'Quasi tutti gli incantesimi costano un\'azione. Alcuni costano un\'azione bonus, e allora nello stesso turno gli altri incantesimi che puoi lanciare sono solo trucchetti.',
    ],
    [
        'nome' => 'Scattare',
        'costo' => 'azione',
        'testo' => 'Raddoppi il movimento di questo turno. Serve per arrivare, o per andarsene.',
    ],
    [
        'nome' => 'Disingaggiare',
        'costo' => 'azione',
        'testo' => 'Ti allontani senza che nessuno ti tiri contro l\'attacco d\'opportunità. È il modo di uscire da una mischia senza prendere un colpo alle spalle.',
    ],
    [
        'nome' => 'Schivare',
        'costo' => 'azione',
        'testo' => 'Fino al tuo turno successivo chiunque ti attacchi ha svantaggio, e tu hai vantaggio ai tiri salvezza su Destrezza. Rinunci ad attaccare per non essere colpito: quando i punti ferita sono pochi vale più di un attacco.',
    ],
    [
        'nome' => 'Aiutare',
        'costo' => 'azione',
        'testo' => 'Dai vantaggio a un compagno: alla sua prossima prova, oppure al suo prossimo attacco contro un nemico entro 1,5 metri da te.',
    ],
    [
        'nome' => 'Nascondersi',
        'costo' => 'azione',
        'testo' => 'Una prova di Destrezza (Furtività). Se riesci, chi ti attacca ha svantaggio e tu hai vantaggio — che è anche il modo in cui un ladro si guadagna l\'attacco furtivo.',
    ],
    [
        'nome' => 'Preparare',
        'costo' => 'azione',
        'testo' => 'Decidi adesso cosa farai e a quale condizione: «se esce dalla porta, tiro». Quando succede, usi la reazione. Serve una reazione libera, e se l\'azione preparata è un incantesimo devi mantenere la concentrazione.',
    ],
    [
        'nome' => 'Cercare',
        'costo' => 'azione',
        'testo' => 'Dedichi il turno a cercare qualcosa: una prova di Saggezza (Percezione) o di Intelligenza (Indagare), a seconda di cosa cerchi.',
    ],
    [
        'nome' => 'Usare un oggetto',
        'costo' => 'azione',
        'testo' => 'Per gli oggetti che chiedono un\'azione, e per interagire con un secondo oggetto nello stesso turno — il primo è gratis.',
    ],
    [
        'nome' => 'Afferrare',
        'costo' => 'azione',
        'testo' => 'Al posto di un attacco. Prova contrapposta di Atletica contro Atletica o Acrobazia: se vinci, la velocità del bersaglio diventa zero.',
    ],
    [
        'nome' => 'Spingere',
        'costo' => 'azione',
        'testo' => 'Al posto di un attacco. Stessa prova contrapposta: se vinci, lo butti a terra prono oppure lo sposti indietro di 1,5 metri.',
    ],
    [
        'nome' => 'Attacco d\'opportunità',
        'costo' => 'reazione',
        'testo' => 'Quando qualcuno che vedi esce dalla tua portata camminando, gli tiri contro un attacco in mischia. Non vale se ha disingaggiato, né se si è teletrasportato.',
    ],
];
