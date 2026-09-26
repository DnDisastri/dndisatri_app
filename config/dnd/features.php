<?php

/*
 * I privilegi di classe: che cosa sa fare un personaggio, livello per livello.
 *
 * Serve alla sezione «Armi» della scheda, che prima elencava solo le armi. Un
 * giocatore che al tavolo si chiede «e io cosa posso fare?» non ha il manuale
 * aperto: ha il telefono in mano.
 *
 * DA DOVE VIENE
 * Regole del 2014, che sono quelle che gioca il gruppo: i privilegi e i livelli
 * vengono dal System Reference Document 5.1, che è in inglese. I nomi italiani
 * vengono dal System Reference Document 5.2.1 in italiano, che è dell'edizione
 * successiva ma traduce quasi tutti gli stessi privilegi: così «Reckless Attack»
 * si chiama qui «Attacco irruento» come sul manuale che avete in mano, e non
 * come sarebbe venuto a me. Dove il privilegio del 2014 non esiste più nel 2024
 * il nome l'ho tradotto io, ed è segnalato riga per riga con «nome mio».
 *
 * ATTRIBUZIONE (richiesta dalla licenza, non facoltativa)
 * Quest'opera include materiale tratto dal System Reference Document 5.1
 * ("SRD 5.1") di Wizards of the Coast LLC, disponibile all'indirizzo
 * https://dnd.wizards.com/resources/systems-reference-document. Il SRD 5.1 è
 * concesso in licenza ai sensi della licenza di attribuzione 4.0 Internazionale
 * di Creative Commons, disponibile all'indirizzo
 * https://creativecommons.org/licenses/by/4.0/legalcode.
 *
 * Quest'opera include materiale tratto dal System Reference Document 5.2.1
 * ("SRD 5.2.1") di Wizards of the Coast LLC, disponibile all'indirizzo
 * https://www.dndbeyond.com/srd. Il SRD 5.2.1 è concesso in licenza ai sensi
 * della licenza di attribuzione 4.0 Internazionale di Creative Commons,
 * disponibile all'indirizzo https://creativecommons.org/licenses/by/4.0/legalcode.
 *
 * COSA NON C'È, DI PROPOSITO
 * - «Aumento dei punteggi di caratteristica» ai livelli 4, 8, 12, 16 e 19: non
 *   è una cosa che si fa in combattimento, ed è già il mestiere della proposta
 *   di salita di livello.
 * - «Incantesimi»: ha una sezione tutta sua, e ripeterlo qui sarebbe rumore.
 * - La riga che dice «scegli una sottoclasse»: la sottoclasse si vede da sé,
 *   nell'intestazione della scheda, e i suoi privilegi stanno più sotto.
 *
 * IL CAMPO «costo»
 * 'azione' | 'bonus' | 'reazione' | 'passivo'. Passivo non vuol dire inutile:
 * vuol dire che ce l'hai sempre e non devi spendere niente per usarlo. È la
 * distinzione che serve al tavolo, quando il turno è tuo e devi decidere.
 */

return [

    'classi' => [

        'Barbaro' => [
            [
                'livello' => 1,
                'nome' => 'Ira',
                'costo' => 'bonus',
                'usi' => 'Un numero di volte al giorno che cresce col livello; tornano col riposo lungo.',
                'testo' => 'Vantaggio alle prove e ai tiri salvezza su Forza, un bonus ai danni con le armi da mischia che usano Forza, e resistenza ai danni contundenti, perforanti e taglienti. Dura un minuto. Non funziona in armatura pesante, e mentre sei in ira non puoi lanciare incantesimi né concentrarti.',
            ],
            [
                'livello' => 1,
                'nome' => 'Difesa senza armatura',
                'costo' => 'passivo',
                'testo' => 'Senza armatura la tua Classe Armatura è 10 più il modificatore di Destrezza e quello di Costituzione. Lo scudo si può portare lo stesso.',
            ],
            [
                'livello' => 2,
                'nome' => 'Attacco irruento',
                'costo' => 'passivo',
                'testo' => 'Al primo attacco del tuo turno puoi decidere di attaccare allo scoperto: hai vantaggio ai tiri per colpire con la Forza, ma fino al tuo turno successivo chiunque ha vantaggio contro di te.',
            ],
            [
                'livello' => 2,
                'nome' => 'Percezione del pericolo',
                'costo' => 'passivo',
                'testo' => 'Vantaggio ai tiri salvezza su Destrezza contro gli effetti che riesci a vedere — trappole e incantesimi. Non vale se sei accecato, assordato o incapacitato.',
            ],
            [
                'livello' => 5,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
            [
                'livello' => 5,
                'nome' => 'Movimento veloce',
                'costo' => 'passivo',
                'testo' => 'La tua velocità aumenta di 3 metri, purché tu non indossi un\'armatura pesante.',
            ],
            [
                'livello' => 7,
                'nome' => 'Istinto ferino',
                'costo' => 'passivo',
                'testo' => 'Vantaggio ai tiri per l\'iniziativa. E se vieni colto di sorpresa, puoi agire lo stesso nel primo turno — a patto di entrare in ira per prima cosa.',
            ],
            [
                'livello' => 9,
                'nome' => 'Colpo brutale',
                'costo' => 'passivo',
                'testo' => 'Quando fai un colpo critico con un\'arma da mischia, tiri un dado di danno in più e lo aggiungi. Diventano due dadi al 13º livello e tre al 17º.',
            ],
            [
                'livello' => 11,
                'nome' => 'Ira implacabile',
                'costo' => 'passivo',
                'testo' => 'Se scendi a 0 punti ferita mentre sei in ira e non muori sul colpo, puoi fare un tiro salvezza su Costituzione con CD 10 per restare invece a 1. Ogni volta che ci riprovi nella stessa ira, la CD sale di 5.',
            ],
            [
                'livello' => 15,
                'nome' => 'Ira persistente',
                'costo' => 'passivo',
                'testo' => 'L\'ira finisce solo se sei incapacitato o se decidi tu di farla finire: non si spegne più da sola perché non hai colpito nessuno.',
            ],
            [
                'livello' => 18,
                'nome' => 'Potenza indomabile',
                'costo' => 'passivo',
                'testo' => 'Se tiri un dado per una prova di Forza e fai meno del tuo punteggio di Forza, vale il punteggio.',
            ],
            [
                'livello' => 20,
                'nome' => 'Campione primordiale',
                'costo' => 'passivo',
                'testo' => 'Forza e Costituzione aumentano di 4, e per te il massimo di quei due punteggi diventa 24.',
            ],
        ],

        'Bardo' => [
            [
                'livello' => 1,
                'nome' => 'Ispirazione bardica',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo modificatore di Carisma; tornano col riposo lungo, e dal 5º livello anche col breve.',
                'testo' => 'Dai un dado d6 a un compagno entro 18 metri. Nei dieci minuti seguenti può aggiungerlo a una prova, a un tiro per colpire o a un tiro salvezza, anche dopo aver tirato ma prima di sapere com\'è andata. Il dado cresce: d8 al 5º, d10 al 10º, d12 al 15º.',
            ],
            [
                'livello' => 2,
                'nome' => 'Factotum',
                'costo' => 'passivo',
                'testo' => 'Aggiungi metà del tuo bonus di competenza, arrotondata per difetto, a tutte le prove di caratteristica in cui non sei già competente.',
            ],
            [
                'livello' => 2,
                'nome' => 'Canzone del riposo',
                'costo' => 'passivo',
                'testo' => 'Se suoni o canti durante un riposo breve, ogni compagno che recupera punti ferita spendendo dadi vita ne recupera 1d6 in più. Il dado cresce col livello: d8 al 9º, d10 al 13º, d12 al 17º.',
            ],
            [
                'livello' => 3,
                'nome' => 'Esperto',
                'costo' => 'passivo',
                'testo' => 'Scegli due abilità in cui sei competente: il bonus di competenza per quelle raddoppia. Al 10º livello scegli altre due abilità.',
            ],
            [
                'livello' => 5,
                'nome' => 'Fonte d\'ispirazione',
                'costo' => 'passivo',
                'testo' => 'Gli usi dell\'ispirazione bardica tornano anche col riposo breve, e non solo col lungo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Controcanto',
                'costo' => 'azione',
                'testo' => 'Cominci a suonare. Per il minuto seguente, tu e chiunque ti senta entro 9 metri avete vantaggio ai tiri salvezza contro la paura e contro l\'ammaliamento. Finisce se resti incapacitato o zitto.',
            ],
            [
                'livello' => 10,
                'nome' => 'Segreti magici',
                'costo' => 'passivo',
                'testo' => 'Impari due incantesimi presi da qualunque lista, di livello che tu possa lanciare: diventano incantesimi da bardo a tutti gli effetti. Succede di nuovo al 14º e al 18º livello.',
            ],
            [
                'livello' => 20,
                'nome' => 'Ispirazione superiore',
                'costo' => 'passivo',
                'testo' => 'Quando tiri l\'iniziativa e non ti restano usi dell\'ispirazione bardica, ne recuperi uno.',
            ],
        ],

        'Chierico' => [
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità',
                'costo' => 'azione',
                'usi' => 'Una volta per riposo, breve o lungo. Diventano due al 6º livello e tre al 18º.',
                'testo' => 'Incanali l\'energia del tuo dio per un effetto preciso: Scacciare non morti, più l\'effetto che ti concede il tuo dominio.',
            ],
            [
                'livello' => 2,
                'nome' => 'Scacciare non morti',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Ogni non morto entro 9 metri che ti veda o ti senta fa un tiro salvezza su Saggezza. Chi fallisce è scacciato per un minuto: deve allontanarsi da te il più possibile e non può avvicinarsi. Il danno gli fa riprendere il controllo.',
            ],
            [
                'livello' => 5,
                'nome' => 'Distruggere non morti',
                'costo' => 'passivo',
                'testo' => 'Quando scacci i non morti, quelli abbastanza deboli non scappano: vengono distrutti sul colpo. La soglia sale col livello — grado sfida 1/2 al 5º, 1 all\'8º, 2 all\'11º, 3 al 14º, 4 al 17º.',
            ],
            [
                'livello' => 10,
                'nome' => 'Intervento divino',
                'costo' => 'azione',
                'usi' => 'Se funziona, non si può riprovare per sette giorni; se fallisce, si riprova il giorno dopo.',
                'testo' => 'Chiedi aiuto al tuo dio. Tira percentuali: se fai meno del tuo livello da chierico, interviene davvero — e come, lo decide il dungeon master. Dal 20º livello riesce sempre.',
            ],
        ],

        'Druido' => [
            [
                'livello' => 1,
                'nome' => 'Druidico',
                'costo' => 'passivo',
                'testo' => 'Conosci il druidico, la lingua segreta dei druidi: puoi parlarla e lasciare messaggi nascosti, che chi la conosce trova senza sforzo e gli altri notano solo con una prova di Saggezza (Percezione) riuscita.',
            ],
            [
                'livello' => 2,
                'nome' => 'Forma selvatica',
                'costo' => 'azione',
                'usi' => 'Due volte, e tornano col riposo breve o lungo.',
                'testo' => 'Ti trasformi in una bestia che hai già visto, per un numero di ore pari a metà del tuo livello da druido. Torni normale quando vuoi, o quando i punti ferita della bestia finiscono. Quali bestie puoi diventare dipende dal livello: al 2º niente che nuoti o voli, al 4º si può nuotare, all\'8º volare.',
            ],
            [
                'livello' => 18,
                'nome' => 'Corpo senza età',
                'costo' => 'passivo',
                'testo' => 'Invecchi di un anno ogni dieci che passano, e non puoi essere invecchiato per magia.',
            ],
            [
                'livello' => 18,
                'nome' => 'Incantesimi bestiali',
                'costo' => 'passivo',
                'testo' => 'In forma selvatica puoi lanciare incantesimi con le sole componenti verbali e somatiche: non ti servono più mani da umano.',
            ],
            [
                'livello' => 20,
                'nome' => 'Arcidruido',
                'costo' => 'passivo',
                'testo' => 'La forma selvatica non si consuma più: puoi usarla quante volte vuoi. E puoi ignorare le componenti verbali e somatiche degli incantesimi da druido, e anche quelle materiali che non costano nulla.',
            ],
        ],

        'Guerriero' => [
            [
                'livello' => 1,
                'nome' => 'Stile di combattimento',
                'costo' => 'passivo',
                'testo' => 'Scegli un modo di combattere e lo sai fare meglio di chiunque: arciere, difensore, duellante, armi grandi, due armi, protezione. Quello che hai scelto sta scritto sulla tua scheda di carta.',
            ],
            [
                'livello' => 1,
                'nome' => 'Recuperare energie',
                'costo' => 'bonus',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Riprendi fiato e recuperi 1d10 punti ferita più il tuo livello da guerriero.',
            ],
            [
                'livello' => 2,
                'nome' => 'Azione impetuosa',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo breve o lungo. Diventano due al 17º livello.',
                'testo' => 'Nel tuo turno fai un\'azione in più, oltre a quella normale e all\'azione bonus. Una sola volta per turno.',
            ],
            [
                'livello' => 5,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una. Diventano tre all\'11º livello e quattro al 20º.',
            ],
            [
                'livello' => 9,
                'nome' => 'Indomabile',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo. Diventano due al 13º livello e tre al 17º.',
                'testo' => 'Se fallisci un tiro salvezza puoi ripeterlo, e devi tenere il secondo risultato.',
            ],
        ],

        'Ladro' => [
            [
                'livello' => 1,
                'nome' => 'Attacco furtivo',
                'costo' => 'passivo',
                'testo' => 'Una volta per turno, se hai vantaggio al tiro per colpire — oppure se un alleato è addosso al bersaglio e tu non sei in svantaggio — aggiungi 1d6 ai danni. Vale con le armi con finezza o a distanza. Il dado cresce di 1d6 ogni due livelli: 10d6 al 20º.',
            ],
            [
                'livello' => 1,
                'nome' => 'Maestria',
                'costo' => 'passivo',
                'testo' => 'Scegli due abilità in cui sei competente, oppure una e gli arnesi da scasso: il bonus di competenza per quelle raddoppia. Al 6º livello scegli altre due.',
            ],
            [
                'livello' => 1,
                'nome' => 'Gergo ladresco',
                'costo' => 'passivo',
                'testo' => 'Conosci il codice dei ladri: parole e segni che nascondono un discorso dentro una conversazione qualunque. Ci vuole quattro volte il tempo di dire la stessa cosa, e chi non lo conosce non si accorge di niente.',
            ],
            [
                'livello' => 2,
                'nome' => 'Azione scaltra',
                'costo' => 'bonus',
                'testo' => 'Ogni turno, come azione bonus, puoi Scattare, Disingaggiare o Nasconderti. È il privilegio che fa del ladro quello che entra ed esce dalla mischia senza farsi toccare.',
            ],
            [
                'livello' => 5,
                'nome' => 'Schivata prodigiosa',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno che vedi ti colpisce con un attacco, dimezzi i danni di quel colpo.',
            ],
            [
                'livello' => 7,
                'nome' => 'Elusione',
                'costo' => 'passivo',
                'testo' => 'Contro gli effetti che chiedono un tiro salvezza su Destrezza per dimezzare i danni — una palla di fuoco, il soffio di un drago — se il tiro riesce non prendi niente, e se fallisce prendi metà.',
            ],
            [
                'livello' => 11,
                'nome' => 'Dote affidabile',
                'costo' => 'passivo',
                'testo' => 'Nelle prove in cui applichi il bonus di competenza, un tiro del dado sotto il 10 vale 10.',
            ],
            [
                'livello' => 14,
                'nome' => 'Percezione cieca',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Sai dov\'è ogni creatura nascosta o invisibile entro 3 metri, purché tu non sia sordo.',
            ],
            [
                'livello' => 15,
                'nome' => 'Mente sfuggente',
                'costo' => 'passivo',
                'testo' => 'Diventi competente nei tiri salvezza su Saggezza.',
            ],
            [
                'livello' => 18,
                'nome' => 'Inafferrabile',
                'costo' => 'passivo',
                'testo' => 'Nessuno ha vantaggio contro di te finché non sei incapacitato.',
            ],
            [
                'livello' => 20,
                'nome' => 'Pietra della buona fortuna',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Trasformi un tiro per colpire mancato in un colpo andato a segno, oppure fai valere 20 il dado di una prova di caratteristica.',
            ],
        ],

        'Mago' => [
            [
                'livello' => 1,
                'nome' => 'Recupero arcano',
                'costo' => 'passivo',
                'nome_mio' => true,
                'usi' => 'Una volta al giorno, durante un riposo breve.',
                'testo' => 'Riprendi fiato sui libri e recuperi slot incantesimo spesi, per un totale di livelli pari a metà del tuo livello da mago arrotondato per eccesso. Nessuno slot di 6º livello o più.',
            ],
            [
                'livello' => 18,
                'nome' => 'Maestria negli incantesimi',
                'costo' => 'passivo',
                'testo' => 'Scegli un incantesimo di 1º livello e uno di 2º fra quelli che hai nel libro: puoi lanciarli al loro livello base senza spendere slot, quante volte vuoi.',
            ],
            [
                'livello' => 20,
                'nome' => 'Incantesimi personali',
                'costo' => 'passivo',
                'usi' => 'Uno per incantesimo; tornano col riposo breve o lungo.',
                'testo' => 'Scegli due incantesimi di 3º livello: li hai sempre preparati e puoi lanciarli una volta ciascuno senza spendere slot.',
            ],
        ],

        'Monaco' => [
            [
                'livello' => 1,
                'nome' => 'Difesa senza armatura',
                'costo' => 'passivo',
                'testo' => 'Senza armatura e senza scudo la tua Classe Armatura è 10 più il modificatore di Destrezza e quello di Saggezza.',
            ],
            [
                'livello' => 1,
                'nome' => 'Arti marziali',
                'costo' => 'passivo',
                'testo' => 'Con i colpi senz\'armi e con le armi da monaco puoi usare Destrezza al posto di Forza, i danni base diventano un dado che cresce col livello (1d4, poi 1d6 al 5º, 1d8 all\'11º, 1d10 al 17º), e quando attacchi puoi tirare un colpo senz\'armi come azione bonus.',
            ],
            [
                'livello' => 2,
                'nome' => 'Punti ki',
                'costo' => 'passivo',
                'nome_mio' => true,
                'usi' => 'Tanti quanto il tuo livello da monaco; tornano col riposo breve o lungo.',
                'testo' => 'L\'energia che alimenta tutto il resto: raffica di colpi, difesa paziente, passo del vento e i privilegi che vengono dopo.',
            ],
            [
                'livello' => 2,
                'nome' => 'Raffica di colpi',
                'costo' => 'bonus',
                'usi' => 'Costa 1 punto ki.',
                'testo' => 'Subito dopo aver fatto l\'azione di Attacco, tiri due colpi senz\'armi come azione bonus.',
            ],
            [
                'livello' => 2,
                'nome' => 'Difesa paziente',
                'costo' => 'bonus',
                'usi' => 'Costa 1 punto ki.',
                'testo' => 'Fai l\'azione di Schivare come azione bonus.',
            ],
            [
                'livello' => 2,
                'nome' => 'Passo del vento',
                'costo' => 'bonus',
                'usi' => 'Costa 1 punto ki.',
                'testo' => 'Fai Disingaggiare o Scattare come azione bonus, e per quel turno la distanza dei tuoi salti raddoppia.',
            ],
            [
                'livello' => 2,
                'nome' => 'Movimento senza armatura',
                'costo' => 'passivo',
                'testo' => 'Senza armatura e senza scudo la tua velocità aumenta di 3 metri, e cresce ancora salendo di livello. Dal 9º corri sulle pareti e sull\'acqua, finché il turno non finisce.',
            ],
            [
                'livello' => 3,
                'nome' => 'Deviare proiettili',
                'costo' => 'reazione',
                'nome_mio' => true,
                'testo' => 'Quando un attacco a distanza con un\'arma ti colpisce, riduci i danni di 1d10 più Destrezza più il tuo livello da monaco. Se li azzeri e hai una mano libera, prendi il proiettile al volo — e spendendo 1 punto ki puoi rilanciarlo come parte della stessa reazione.',
            ],
            [
                'livello' => 4,
                'nome' => 'Caduta lenta',
                'costo' => 'reazione',
                'testo' => 'Mentre stai cadendo, riduci i danni della caduta di cinque volte il tuo livello da monaco.',
            ],
            [
                'livello' => 5,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
            [
                'livello' => 5,
                'nome' => 'Colpo stordente',
                'costo' => 'passivo',
                'usi' => 'Costa 1 punto ki.',
                'testo' => 'Quando colpisci qualcuno con un attacco in mischia, può fare un tiro salvezza su Costituzione: se fallisce resta stordito fino alla fine del tuo turno successivo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Colpi potenziati dal ki',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'I tuoi colpi senz\'armi contano come magici: le resistenze ai danni non magici non ti fermano più.',
            ],
            [
                'livello' => 7,
                'nome' => 'Elusione',
                'costo' => 'passivo',
                'testo' => 'Contro gli effetti che chiedono un tiro salvezza su Destrezza per dimezzare i danni, se il tiro riesce non prendi niente, e se fallisce prendi metà.',
            ],
            [
                'livello' => 7,
                'nome' => 'Quiete della mente',
                'costo' => 'azione',
                'nome_mio' => true,
                'testo' => 'Ti liberi da solo di un effetto che ti sta ammaliando o spaventando.',
            ],
            [
                'livello' => 10,
                'nome' => 'Purezza del corpo',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Sei immune alle malattie e ai veleni.',
            ],
            [
                'livello' => 13,
                'nome' => 'Lingua del sole e della luna',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Capisci qualunque lingua parlata, e chiunque capisce te.',
            ],
            [
                'livello' => 14,
                'nome' => 'Anima di diamante',
                'costo' => 'passivo',
                'nome_mio' => true,
                'usi' => 'Ripetere un tiro salvezza costa 1 punto ki.',
                'testo' => 'Sei competente in tutti i tiri salvezza, e quando ne fallisci uno puoi ripeterlo tenendo il secondo risultato.',
            ],
            [
                'livello' => 15,
                'nome' => 'Corpo senza età',
                'costo' => 'passivo',
                'testo' => 'Non invecchi più per magia, e non hai bisogno di cibo né d\'acqua.',
            ],
            [
                'livello' => 18,
                'nome' => 'Corpo vuoto',
                'costo' => 'azione',
                'nome_mio' => true,
                'usi' => 'Costa 4 punti ki per l\'invisibilità, 8 per il viaggio planare.',
                'testo' => 'Diventi invisibile per un minuto, con resistenza a tutti i danni tranne quelli da forza. Oppure lanci proiezione astrale su te stesso, senza componenti materiali.',
            ],
            [
                'livello' => 20,
                'nome' => 'Sé perfetto',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Se tiri l\'iniziativa e non ti restano punti ki, ne recuperi 4.',
            ],
        ],

        'Paladino' => [
            [
                'livello' => 1,
                'nome' => 'Percezione divina',
                'costo' => 'azione',
                'nome_mio' => true,
                'usi' => 'Uno più il modificatore di Carisma; tornano col riposo lungo.',
                'testo' => 'Fino alla fine del tuo turno successivo senti dove sono, entro 18 metri, i celestiali, gli immondi e i non morti non nascosti dietro una copertura totale — e i luoghi o gli oggetti consacrati o profanati.',
            ],
            [
                'livello' => 1,
                'nome' => 'Imposizione delle mani',
                'costo' => 'azione',
                'usi' => 'Una riserva di punti pari a cinque volte il tuo livello da paladino; torna col riposo lungo.',
                'testo' => 'Tocchi qualcuno e spendi quanti punti vuoi dalla riserva: altrettanti punti ferita recuperati. Cinque punti al posto della cura tolgono invece una malattia o un veleno.',
            ],
            [
                'livello' => 2,
                'nome' => 'Stile di combattimento',
                'costo' => 'passivo',
                'testo' => 'Scegli un modo di combattere e lo sai fare meglio di chiunque. Quello che hai scelto sta scritto sulla tua scheda di carta.',
            ],
            [
                'livello' => 2,
                'nome' => 'Punizione divina',
                'costo' => 'passivo',
                'usi' => 'Costa uno slot incantesimo, e si decide dopo aver visto che il colpo è andato a segno.',
                'testo' => 'Quando colpisci con un\'arma da mischia puoi bruciare uno slot per aggiungere 2d8 danni radiosi, più 1d8 per ogni livello dello slot oltre il primo, fino a 5d8. Contro non morti e immondi un altro 1d8 ancora.',
            ],
            [
                'livello' => 3,
                'nome' => 'Salute divina',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'La magia che ti scorre dentro ti rende immune alle malattie.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Gli effetti sono quelli del tuo giuramento: cambiano da sottoclasse a sottoclasse.',
            ],
            [
                'livello' => 5,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
            [
                'livello' => 6,
                'nome' => 'Aura di protezione',
                'costo' => 'passivo',
                'testo' => 'Tu e ogni alleato entro 3 metri aggiungete il tuo modificatore di Carisma a tutti i tiri salvezza, minimo +1. Il raggio arriva a 9 metri al 18º livello.',
            ],
            [
                'livello' => 10,
                'nome' => 'Aura di coraggio',
                'costo' => 'passivo',
                'testo' => 'Tu e ogni alleato entro 3 metri non potete essere spaventati. Il raggio arriva a 9 metri al 18º livello.',
            ],
            [
                'livello' => 11,
                'nome' => 'Punizione divina migliorata',
                'costo' => 'passivo',
                'testo' => 'Ogni colpo andato a segno con un\'arma da mischia fa 1d8 danni radiosi in più, senza spendere niente.',
            ],
            [
                'livello' => 14,
                'nome' => 'Tocco purificatore',
                'costo' => 'azione',
                'nome_mio' => true,
                'usi' => 'Tante volte quanto il tuo modificatore di Carisma, minimo una; tornano col riposo lungo.',
                'testo' => 'Tocchi una creatura e fai finire un incantesimo su di lei — il tuo o quello di un altro.',
            ],
        ],

        'Ranger' => [
            [
                'livello' => 1,
                'nome' => 'Nemico prescelto',
                'costo' => 'passivo',
                'testo' => 'Hai studiato una categoria di creature: vantaggio alle prove di Saggezza (Sopravvivenza) per seguirne le tracce e alle prove di Intelligenza per ricordare cosa sai di loro. Impari anche una lingua che parlano. Al 6º e al 14º livello ne scegli un\'altra.',
            ],
            [
                'livello' => 1,
                'nome' => 'Esploratore naturale',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'In un tipo di terreno che conosci bene: il terreno difficile non ti rallenta, non ti perdi mai se non per magia, resti all\'erta anche mentre fai altro, viaggi da solo a doppia velocità e trovi il doppio del cibo. Al 6º e al 10º livello aggiungi un altro terreno.',
            ],
            [
                'livello' => 2,
                'nome' => 'Stile di combattimento',
                'costo' => 'passivo',
                'testo' => 'Scegli un modo di combattere e lo sai fare meglio di chiunque. Quello che hai scelto sta scritto sulla tua scheda di carta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Consapevolezza primordiale',
                'costo' => 'azione',
                'nome_mio' => true,
                'usi' => 'Costa uno slot incantesimo.',
                'testo' => 'Senti se entro 1,5 km — 9,5 in un terreno che conosci — ci sono aberrazioni, celestiali, draghi, elementali, fatati, immondi o non morti. Sai che ci sono, non dove.',
            ],
            [
                'livello' => 5,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
            [
                'livello' => 8,
                'nome' => 'Passo del territorio',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Il terreno difficile non magico non ti costa più movimento, e attraversi rovi e spine senza prendere danni né rallentare.',
            ],
            [
                'livello' => 10,
                'nome' => 'Nascondersi in bella vista',
                'costo' => 'passivo',
                'nome_mio' => true,
                'testo' => 'Un minuto di lavoro a mimetizzarti contro una superficie e ottieni +10 alle prove di Furtività per restare nascosto, finché non ti muovi o non fai nient\'altro.',
            ],
            [
                'livello' => 14,
                'nome' => 'Svanire',
                'costo' => 'bonus',
                'nome_mio' => true,
                'testo' => 'Ti nascondi come azione bonus. E nessuno può seguire le tue tracce, se non per magia.',
            ],
            [
                'livello' => 18,
                'nome' => 'Sensi ferini',
                'costo' => 'passivo',
                'testo' => 'Contro chi è invisibile non hai svantaggio ai tiri per colpire, e sai dov\'è ogni creatura entro 9 metri che non sia nascosta né fuori dai tuoi sensi.',
            ],
            [
                'livello' => 20,
                'nome' => 'Sterminatore di nemici',
                'costo' => 'passivo',
                'testo' => 'Una volta per turno, contro un tuo nemico prescelto, aggiungi il modificatore di Saggezza al tiro per colpire o al tiro dei danni.',
            ],
        ],

        'Stregone' => [
            [
                'livello' => 2,
                'nome' => 'Fonte di magia',
                'costo' => 'passivo',
                'usi' => 'Punti stregoneria pari al tuo livello da stregone; tornano col riposo lungo.',
                'testo' => 'La magia che hai nel sangue diventa una riserva di punti, che alimenta la metamagia e si scambia con gli slot incantesimo.',
            ],
            [
                'livello' => 2,
                'nome' => 'Lancio flessibile',
                'costo' => 'bonus',
                'nome_mio' => true,
                'testo' => 'Trasformi punti stregoneria in uno slot incantesimo, o uno slot in punti stregoneria. Uno slot di 1º livello costa 2 punti, di 2º 3 punti, di 3º 5, di 4º 6, di 5º 7.',
            ],
            [
                'livello' => 3,
                'nome' => 'Metamagia',
                'costo' => 'passivo',
                'usi' => 'Ogni opzione ha il suo costo in punti stregoneria. Una sola per incantesimo.',
                'testo' => 'Pieghi i tuoi incantesimi: più lontani, più veloci, senza parole, su due bersagli, con il tiro salvezza in svantaggio. Scegli due modi al 3º livello, un altro al 10º e un altro al 17º.',
            ],
            [
                'livello' => 20,
                'nome' => 'Ripristino stregonesco',
                'costo' => 'passivo',
                'nome_mio' => true,
                'usi' => 'Una volta al giorno, dopo un riposo breve.',
                'testo' => 'Recuperi 4 punti stregoneria.',
            ],
        ],

        'Warlock' => [
            [
                'livello' => 1,
                'nome' => 'Magia del patto',
                'costo' => 'passivo',
                'testo' => 'I tuoi slot sono pochi ma tornano col **riposo breve**, e sono sempre del livello più alto che puoi lanciare. È il motivo per cui un warlock lancia tutto al massimo e poi si siede un\'ora.',
            ],
            [
                'livello' => 2,
                'nome' => 'Suppliche occulte',
                'costo' => 'passivo',
                'testo' => 'Frammenti di sapere proibito che cambiano quello che sai fare: vedere nel buio, leggere qualunque scrittura, lanciare un incantesimo senza spendere slot. Ne scegli due al 2º livello e altre salendo. Quali hai preso sta scritto sulla tua scheda di carta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Dono del patto',
                'costo' => 'passivo',
                'testo' => 'Il tuo patrono ti concede una cosa sola, e la scegli tu: un famiglio (Patto della Catena), un\'arma che evochi dal nulla (Patto della Lama), o un libro di trucchetti in più (Patto del Tomo).',
            ],
            [
                'livello' => 11,
                'nome' => 'Arcanum mistico',
                'costo' => 'passivo',
                'usi' => 'Una volta per incantesimo; tornano col riposo lungo.',
                'testo' => 'Scegli un incantesimo di 6º livello: lo lanci una volta al giorno senza spendere slot. Al 13º livello ne aggiungi uno di 7º, al 15º uno di 8º, al 17º uno di 9º.',
            ],
            [
                'livello' => 20,
                'nome' => 'Maestro dell\'occulto',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Un minuto di supplica al tuo patrono e riprendi tutti gli slot della magia del patto.',
            ],
        ],
    ],

    /*
     * I privilegi delle sottoclassi.
     *
     * Le chiavi sono i nomi esatti di config/dnd/subclasses.php: se non
     * combaciano, la scheda non trova niente e non dice niente.
     *
     * Qui c'è la parte scomoda. Il SRD — tutti e due — contiene **una sola
     * sottoclasse per classe**, per scelta di Wizards: dodici su centotto. Le
     * altre novantasei sono nei manuali, e di quelle si possono scrivere
     * riassunti originali ma non copiare il testo.
     *
     * Una sottoclasse senza privilegi scritti qui non rompe niente: la scheda
     * mostra la riga che c'è già in subclasses.php e dice che i privilegi non
     * sono ancora stati scritti. Meglio un buco dichiarato di una cosa
     * inventata che sembra vera.
     */
    'sottoclassi' => [

        'Cammino del Berserker' => [
            [
                'livello' => 3,
                'nome' => 'Frenesia',
                'costo' => 'passivo',
                'testo' => 'Quando entri in ira puoi andare in frenesia: per tutta la durata, ogni turno, fai un attacco in più come azione bonus. Quando l\'ira finisce sei stremato.',
            ],
            [
                'livello' => 6,
                'nome' => 'Ira incontenibile',
                'costo' => 'passivo',
                'testo' => 'Mentre sei in ira non puoi essere ammaliato né spaventato. Se lo eri già, l\'effetto resta sospeso finché l\'ira dura.',
            ],
            [
                'livello' => 10,
                'nome' => 'Presenza intimidatoria',
                'costo' => 'azione',
                'testo' => 'Terrorizzi una creatura entro 9 metri: tiro salvezza su Saggezza contro la CD del tuo Carisma, e se fallisce resta spaventata fino alla fine del tuo turno successivo. Nei turni seguenti puoi tenerla sotto con un\'altra azione.',
            ],
            [
                'livello' => 14,
                'nome' => 'Ritorsione',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno entro 1,5 metri ti fa danno, gli tiri contro un attacco in mischia.',
            ],
        ],

        'Collegio della Sapienza' => [
            [
                'livello' => 3,
                'nome' => 'Competenze bonus',
                'costo' => 'passivo',
                'testo' => 'Diventi competente in tre abilità a tua scelta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Parole taglienti',
                'costo' => 'reazione',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Quando qualcuno entro 18 metri tira per colpire, fa una prova di caratteristica o tira i danni, spendi il dado dell\'ispirazione e glielo sottrai. Puoi farlo dopo il tiro, ma prima che si sappia com\'è andata.',
            ],
            [
                'livello' => 6,
                'nome' => 'Scoperte magiche',
                'costo' => 'passivo',
                'testo' => 'Impari due incantesimi da qualunque lista, in anticipo su tutti gli altri bardi.',
            ],
            [
                'livello' => 14,
                'nome' => 'Abilità impareggiabile',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Quando fai una prova di caratteristica puoi spendere il dado dell\'ispirazione e sommarlo, dopo aver tirato ma prima di sapere l\'esito.',
            ],
        ],

        'Dominio della Vita' => [
            [
                'livello' => 1,
                'nome' => 'Discepolo della vita',
                'costo' => 'passivo',
                'testo' => 'Ogni incantesimo di 1º livello o più che ridà punti ferita ne ridà 2 in più, più il livello dello slot speso. Vale anche la competenza nelle armature pesanti, che arriva insieme.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: preservare vita',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Hai punti ferita da distribuire pari a cinque volte il tuo livello da chierico: li dividi come vuoi fra le creature entro 9 metri, ma nessuna può superare la metà del proprio massimo. Su di te non funziona.',
            ],
            [
                'livello' => 6,
                'nome' => 'Guaritore benedetto',
                'costo' => 'passivo',
                'testo' => 'Quando curi qualcun altro con un incantesimo di 1º livello o più, curi anche te stesso di 2 punti ferita più il livello dello slot.',
            ],
            [
                'livello' => 8,
                'nome' => 'Colpo divino',
                'costo' => 'passivo',
                'usi' => 'Una volta per turno.',
                'testo' => 'Quando colpisci con un\'arma, aggiungi 1d8 danni radiosi. Diventano 2d8 al 14º livello.',
            ],
            [
                'livello' => 17,
                'nome' => 'Guarigione suprema',
                'costo' => 'passivo',
                'testo' => 'Quando un tuo incantesimo curerebbe tirando dei dadi, non tiri: prendi il massimo di ogni dado.',
            ],
        ],

        'Circolo della Terra' => [
            [
                'livello' => 2,
                'nome' => 'Recupero naturale',
                'costo' => 'passivo',
                'usi' => 'Una volta al giorno, durante un riposo breve.',
                'testo' => 'Recuperi slot incantesimo spesi per un totale di livelli pari a metà del tuo livello da druido arrotondato per eccesso. Niente slot di 6º livello o più. Arriva insieme a un trucchetto in più.',
            ],
            [
                'livello' => 6,
                'nome' => 'Passo del territorio',
                'costo' => 'passivo',
                'testo' => 'Il terreno difficile non magico non ti costa movimento, e attraversi rovi e spine senza prendere danni. Le piante mosse per magia a bloccarti ti lasciano passare, se fai il tiro salvezza.',
            ],
            [
                'livello' => 10,
                'nome' => 'Interdizione della Natura',
                'costo' => 'passivo',
                'testo' => 'Elementali e fatati non possono ammaliarti né spaventarti, e sei immune ai veleni e alle malattie.',
            ],
            [
                'livello' => 14,
                'nome' => 'Rifugio della Natura',
                'costo' => 'passivo',
                'testo' => 'Bestie e vegetali devono superare un tiro salvezza su Saggezza per attaccarti: se falliscono, scelgono un altro bersaglio.',
            ],
        ],

        'Campione' => [
            [
                'livello' => 3,
                'nome' => 'Critico migliorato',
                'costo' => 'passivo',
                'testo' => 'I tuoi attacchi con le armi fanno critico anche con un 19, e non solo con un 20.',
            ],
            [
                'livello' => 7,
                'nome' => 'Atleta straordinario',
                'costo' => 'passivo',
                'testo' => 'Aggiungi metà del bonus di competenza, arrotondata per eccesso, a ogni prova di Forza, Destrezza o Costituzione in cui non sei già competente. E i tuoi salti in lungo con la rincorsa guadagnano metri pari al modificatore di Forza.',
            ],
            [
                'livello' => 10,
                'nome' => 'Stile di combattimento aggiuntivo',
                'costo' => 'passivo',
                'testo' => 'Scegli un secondo stile di combattimento.',
            ],
            [
                'livello' => 15,
                'nome' => 'Critico superiore',
                'costo' => 'passivo',
                'testo' => 'I tuoi attacchi con le armi fanno critico con 18, 19 e 20.',
            ],
            [
                'livello' => 18,
                'nome' => 'Sopravvissuto',
                'costo' => 'passivo',
                'testo' => 'All\'inizio di ogni tuo turno, se ti resta meno di metà dei punti ferita e non sei a zero, ne recuperi 5 più il modificatore di Costituzione.',
            ],
        ],

        'Ladro' => [
            [
                'livello' => 3,
                'nome' => 'Mani veloci',
                'costo' => 'bonus',
                'testo' => 'L\'azione scaltra ti serve anche per rubare o fare giochi di prestigio con Destrezza (Rapidità di mano), per aprire una serratura con gli arnesi, o per usare un oggetto.',
            ],
            [
                'livello' => 3,
                'nome' => 'Lavoro sui tetti',
                'costo' => 'passivo',
                'testo' => 'Arrampicarti non ti costa movimento in più, e i tuoi salti in lungo con la rincorsa guadagnano metri pari al modificatore di Destrezza.',
            ],
            [
                'livello' => 9,
                'nome' => 'Furtività suprema',
                'costo' => 'passivo',
                'testo' => 'Hai vantaggio alle prove di Destrezza (Furtività), se in quel turno ti muovi al massimo a metà velocità.',
            ],
            [
                'livello' => 13,
                'nome' => 'Usare oggetti magici',
                'costo' => 'passivo',
                'testo' => 'Ignori tutti i requisiti di classe, razza e livello degli oggetti magici: quella bacchetta da mago la usi lo stesso.',
            ],
            [
                'livello' => 17,
                'nome' => 'Riflessi del ladro',
                'costo' => 'passivo',
                'testo' => 'Nel primo round di ogni combattimento hai due turni: il primo alla tua iniziativa, il secondo a quella meno 10. Non vale se sei stato colto di sorpresa.',
            ],
        ],

        'Invocazione' => [
            [
                'livello' => 2,
                'nome' => 'Sapiente dell\'invocazione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di invocazione nel tuo libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 2,
                'nome' => 'Modellare incantesimi',
                'costo' => 'passivo',
                'testo' => 'Quando lanci un incantesimo di invocazione che colpisce un\'area, scegli un numero di creature pari al livello dello slot più uno: quelle passano indenni. Il tiro salvezza gli riesce automaticamente, e se l\'incantesimo faceva metà danni a chi salva, non ne prendono affatto.',
            ],
            [
                'livello' => 6,
                'nome' => 'Trucchetto potente',
                'costo' => 'passivo',
                'testo' => 'I tuoi trucchetti che chiedono un tiro salvezza fanno comunque metà danni a chi lo supera.',
            ],
            [
                'livello' => 10,
                'nome' => 'Invocazione potenziata',
                'costo' => 'passivo',
                'testo' => 'Aggiungi il modificatore di Intelligenza ai danni di ogni incantesimo di invocazione da mago.',
            ],
            [
                'livello' => 14,
                'nome' => 'Sovraccarico',
                'costo' => 'passivo',
                'usi' => 'Gratis una volta per riposo lungo; dopo, ogni volta fa male.',
                'testo' => 'Un incantesimo da 1º a 5º livello che fa danni li fa al massimo, senza tirare. Dalla seconda volta prima del riposo lungo prendi 2d12 danni necrotici per ogni livello dell\'incantesimo, e ogni volta ancora 1d12 in più.',
            ],
        ],

        'Via della Mano Aperta' => [
            [
                'livello' => 3,
                'nome' => 'Tecnica della mano aperta',
                'costo' => 'passivo',
                'testo' => 'Quando colpisci con la raffica di colpi, il bersaglio deve superare un tiro salvezza o subire una di queste: cade a terra prono, viene spinto indietro di 4,5 metri, oppure perde la reazione fino alla fine del tuo turno successivo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Interezza del corpo',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Ti curi da solo di tre volte il tuo livello da monaco.',
            ],
            [
                'livello' => 11,
                'nome' => 'Tranquillità',
                'costo' => 'passivo',
                'testo' => 'Alla fine di un riposo lungo hai addosso un santuario che dura fino all\'inizio del prossimo: chi vuole attaccarti deve prima superare un tiro salvezza su Saggezza. Finisce se attacchi tu.',
            ],
            [
                'livello' => 17,
                'nome' => 'Palmo tremante',
                'costo' => 'passivo',
                'usi' => 'Costa 3 punti ki. Una vibrazione alla volta.',
                'testo' => 'Con un colpo senz\'armi lasci nel corpo di qualcuno una vibrazione impercettibile, che dura giorni. Quando vuoi, come azione, la fai esplodere: tiro salvezza su Costituzione, e se fallisce scende a 0 punti ferita. Se lo supera prende comunque 10d10 danni necrotici.',
            ],
        ],

        'Giuramento di Devozione' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: arma consacrata',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Per un minuto la tua arma è magica, fa luce e aggiunge il modificatore di Carisma ai tiri per colpire, minimo +1.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: scacciare i profani',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Immondi e non morti entro 9 metri che ti vedono o ti sentono fanno un tiro salvezza su Saggezza: chi fallisce scappa da te per un minuto.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di devozione',
                'costo' => 'passivo',
                'testo' => 'Tu e gli alleati entro 3 metri non potete essere ammaliati. Il raggio arriva a 9 metri al 18º livello.',
            ],
            [
                'livello' => 15,
                'nome' => 'Purezza di spirito',
                'costo' => 'passivo',
                'testo' => 'Hai sempre addosso protezione dal bene e dal male: aberrazioni, celestiali, elementali, fatati, immondi e non morti hanno svantaggio contro di te e non possono ammaliarti, spaventarti né possederti.',
            ],
            [
                'livello' => 20,
                'nome' => 'Nube sacra',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Per un minuto emani luce solare per 9 metri. Gli ostili che cominciano il turno lì dentro prendono 10 danni radiosi, e tu hai vantaggio ai tiri salvezza contro gli incantesimi di immondi e non morti.',
            ],
        ],

        'Cacciatore' => [
            [
                'livello' => 3,
                'nome' => 'Preda del cacciatore',
                'costo' => 'passivo',
                'testo' => 'Scegli come combatti: danni in più contro chi è già ferito, un colpo che rimbalza su un secondo nemico vicino, oppure un bonus ai danni contro le creature più grandi di te. Quale hai scelto sta sulla tua scheda di carta.',
            ],
            [
                'livello' => 7,
                'nome' => 'Tattiche difensive',
                'costo' => 'passivo',
                'testo' => 'Scegli una difesa: non essere più sorpreso dagli attacchi multipli, vantaggio contro la paura, oppure svantaggio a chiunque attacchi qualcun altro che non sia te.',
            ],
            [
                'livello' => 11,
                'nome' => 'Attacco multiplo',
                'costo' => 'azione',
                'testo' => 'Scegli fra una raffica di frecce su un\'area e una sequenza di attacchi in mischia contro nemici diversi.',
            ],
            [
                'livello' => 15,
                'nome' => 'Difesa del cacciatore superiore',
                'costo' => 'reazione',
                'testo' => 'Quando prendi danni, li riduci — di quanto e come dipende dalla difesa che hai scelto.',
            ],
        ],

        'Discendenza Draconica' => [
            [
                'livello' => 1,
                'nome' => 'Antenato draconico',
                'costo' => 'passivo',
                'testo' => 'Scegli un tipo di drago: parli draconico, e le prove di Carisma con i draghi contano il tuo bonus di competenza raddoppiato.',
            ],
            [
                'livello' => 1,
                'nome' => 'Resilienza draconica',
                'costo' => 'passivo',
                'testo' => 'Il tuo massimo di punti ferita sale di uno per ogni livello da stregone, e senza armatura la tua Classe Armatura è 13 più il modificatore di Destrezza.',
            ],
            [
                'livello' => 6,
                'nome' => 'Affinità elementale',
                'costo' => 'passivo',
                'usi' => 'Spendere 1 punto stregoneria aggiunge la resistenza.',
                'testo' => 'Aggiungi il modificatore di Carisma ai danni degli incantesimi del tuo tipo di drago. E spendendo un punto stregoneria hai resistenza a quel tipo di danno per un\'ora.',
            ],
            [
                'livello' => 14,
                'nome' => 'Ali di drago',
                'costo' => 'bonus',
                'testo' => 'Ti spuntano le ali e voli alla tua velocità, finché non le fai sparire. Non funziona se indossi qualcosa che non sia fatto apposta per lasciarle passare.',
            ],
            [
                'livello' => 18,
                'nome' => 'Presenza draconica',
                'costo' => 'azione',
                'usi' => 'Costa 5 punti stregoneria; richiede concentrazione, fino a un minuto.',
                'testo' => 'Emani terrore o meraviglia per 18 metri: chi comincia il turno lì dentro fa un tiro salvezza su Saggezza o resta ammaliato — o spaventato, scegli tu — finché dura.',
            ],
        ],

        'Patrono: l\'Immondo' => [
            [
                'livello' => 1,
                'nome' => 'Benedizione dell\'oscuro',
                'costo' => 'passivo',
                'testo' => 'Ogni volta che riduci un ostile a 0 punti ferita, guadagni punti ferita temporanei pari al modificatore di Carisma più il tuo livello da warlock.',
            ],
            [
                'livello' => 6,
                'nome' => 'Fortuna dell\'oscuro',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Aggiungi 1d10 a una prova di caratteristica o a un tiro salvezza, dopo aver tirato ma prima di sapere com\'è andata.',
            ],
            [
                'livello' => 10,
                'nome' => 'Resilienza immonda',
                'costo' => 'passivo',
                'testo' => 'Alla fine di un riposo scegli un tipo di danno: ne hai resistenza finché non ne scegli un altro. Non vale contro le armi magiche.',
            ],
            [
                'livello' => 14,
                'nome' => 'Scagliare all\'inferno',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Colpisci qualcuno e lo spedisci a fare un giro nei piani inferiori. Sparisce, e all\'inizio del tuo turno successivo torna dov\'era con 10d10 danni psichici addosso — a meno che non sia un celestiale o un immondo, che a casa loro non si fanno niente.',
            ],
        ],

        /*
         * Da qui in giù: le sottoclassi che nel SRD non ci sono.
         *
         * Sono riassunti originali scritti da noi, non testo del manuale.
         * Per adesso ci sono i **primi due scaglioni** di ogni sottoclasse —
         * quelli a cui i personaggi arrivano davvero — e il resto si aggiunge
         * quando qualcuno ci arriva.
         *
         * `da_controllare` segna le voci di cui non siamo sicuri: la scheda le
         * mostra con un avviso, invece di farle passare per verificate.
         */

        'Cammino del Totem Guerriero' => [
            [
                'livello' => 3,
                'nome' => 'Ricerca dello spirito',
                'costo' => 'passivo',
                'testo' => 'Sai lanciare parlare con gli animali e individuare bestie e piante come rituali, senza spendere slot.',
            ],
            [
                'livello' => 3,
                'nome' => 'Spirito totemico',
                'costo' => 'passivo',
                'testo' => 'Scegli un animale guida e l\'ira cambia di conseguenza. Orso: resistenza a tutti i danni tranne quelli psichici, ed è la ragione per cui questa sottoclasse regge le botte. Aquila: disingaggi o scatti come azione bonus. Lupo: i tuoi alleati hanno vantaggio contro chi ti sta entro 1,5 metri.',
            ],
            [
                'livello' => 6,
                'nome' => 'Aspetto della bestia',
                'costo' => 'passivo',
                'testo' => 'Un dono che vale sempre, non solo in ira. Orso: porti il doppio del peso e hai vantaggio quando afferri o spingi. Aquila: vedi da lontano come un\'aquila e non hai svantaggio a percepire alla luce del sole. Lupo: fai viaggiare il gruppo veloce anche di corsa.',
            ],
        ],

        'Cammino del Guardiano Ancestrale' => [
            [
                'livello' => 3,
                'nome' => 'Protettori ancestrali',
                'costo' => 'passivo',
                'testo' => 'Mentre sei in ira, il primo che colpisci in un turno se lo ritrova addosso: fino al tuo turno successivo ha svantaggio contro chiunque non sia te, e i danni che fa agli altri sono dimezzati. È il modo del barbaro di dire «guarda me».',
            ],
            [
                'livello' => 6,
                'nome' => 'Scudo spirituale',
                'costo' => 'reazione',
                'testo' => 'Mentre sei in ira, quando qualcuno entro 9 metri prende danni, gli togli 2d6. Diventano 3d6 al 10º livello e 4d6 al 14º.',
            ],
        ],

        'Cammino dello Zelota' => [
            [
                'livello' => 3,
                'nome' => 'Furia divina',
                'costo' => 'passivo',
                'usi' => 'Una volta per turno, mentre sei in ira.',
                'testo' => 'Il primo colpo di ogni turno fa 1d6 danni in più più metà del tuo livello da barbaro: necrotici o radiosi, scegli tu quando prendi il privilegio.',
            ],
            [
                'livello' => 3,
                'nome' => 'Guerriero della stirpe',
                'costo' => 'passivo',
                'testo' => 'Riportarti in vita non costa niente a nessuno: gli incantesimi che lo fanno non hanno bisogno delle componenti materiali, e ti raggiungono anche se sei morto da un pezzo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Concentrazione fanatica',
                'costo' => 'passivo',
                'usi' => 'Una volta per ira.',
                'testo' => 'Mentre sei in ira, se fallisci un tiro salvezza puoi ripeterlo, e devi tenere il secondo risultato.',
            ],
        ],

        'Cammino dell\'Araldo delle Tempeste' => [
            [
                'livello' => 3,
                'nome' => 'Aura di tempesta',
                'costo' => 'bonus',
                'testo' => 'Mentre sei in ira emani un\'aura di 3 metri, e scegli che tempo fa. Deserto: 2 danni da fuoco a tutti gli ostili quando la attivi. Mare: come azione bonus, un fulmine su uno di loro, con tiro salvezza su Destrezza per dimezzare. Tundra: punti ferita temporanei a te e agli alleati.',
            ],
            [
                'livello' => 6,
                'nome' => 'Anima della tempesta',
                'costo' => 'passivo',
                'testo' => 'Il clima che hai scelto ti entra addosso: resistenza al fuoco e sopportare il caldo (deserto), resistenza ai fulmini e respirare sott\'acqua (mare), resistenza al freddo e sopportare il gelo (tundra).',
            ],
        ],

        'Cammino della Bestia' => [
            [
                'livello' => 3,
                'nome' => 'Forma della bestia',
                'costo' => 'passivo',
                'testo' => 'Quando entri in ira ti spunta un\'arma naturale, e la scegli ogni volta. Artigli: un attacco in più quando fai l\'azione di Attacco. Zanne: chi mordi recupera meno dai suoi tiri, e tu ci guadagni punti ferita. Coda: una reazione che alza la tua Classe Armatura dopo aver visto il tiro.',
            ],
            [
                'livello' => 6,
                'nome' => 'Andatura bestiale',
                'costo' => 'passivo',
                'testo' => 'Alla fine di un riposo scegli come ti muovi: nuotare alla tua velocità e respirare sott\'acqua, arrampicarti anche a testa in giù, oppure saltare come se avessi sempre la rincorsa.',
            ],
        ],

        'Cammino della Magia Selvaggia' => [
            [
                'livello' => 3,
                'nome' => 'Magia selvaggia',
                'costo' => 'passivo',
                'testo' => 'Ogni volta che entri in ira tiri su una tabella e succede qualcosa di magico che non hai scelto: un\'esplosione necrotica, un teletrasporto breve, un\'arma spettrale. Dura finché dura l\'ira. Vieni anche a sapere dove c\'è magia intorno a te.',
            ],
            [
                'livello' => 6,
                'nome' => 'Magia corroborante',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Tocchi un compagno e per dieci minuti aggiunge 1d3 ai suoi tiri per colpire e alle sue prove. Oppure, se è un incantatore, gli fai tornare uno slot speso.',
            ],
        ],

        'Cammino dei Giganti' => [
            [
                'livello' => 3,
                'nome' => 'Fendente elementale',
                'costo' => 'passivo',
                'da_controllare' => true,
                'testo' => 'Mentre sei in ira l\'arma che impugni si carica di un elemento a tua scelta — fuoco, freddo, tuono — e i suoi danni diventano di quel tipo, con un dado in più.',
            ],
            [
                'livello' => 3,
                'nome' => 'Statura del gigante',
                'costo' => 'bonus',
                'da_controllare' => true,
                'testo' => 'Cresci di taglia mentre sei in ira: portata più lunga e vantaggio alle prove di Forza. Impari anche il gigante.',
            ],
            [
                'livello' => 6,
                'nome' => 'Spinta possente',
                'costo' => 'bonus',
                'da_controllare' => true,
                'testo' => 'Mentre sei in ira scagli una creatura vicina, alleata o nemica: chi non vuole andare fa un tiro salvezza su Forza.',
            ],
        ],

        'Collegio del Valore' => [
            [
                'livello' => 3,
                'nome' => 'Competenze bonus',
                'costo' => 'passivo',
                'testo' => 'Armature medie, scudi e armi da guerra: è il bardo che sta in prima fila.',
            ],
            [
                'livello' => 3,
                'nome' => 'Ispirazione da combattimento',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Chi ha il tuo dado può spenderlo anche per i danni di un attacco andato a segno, oppure — come reazione, quando sta per essere colpito — per alzare la propria Classe Armatura.',
            ],
            [
                'livello' => 6,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
        ],

        'Collegio delle Spade' => [
            [
                'livello' => 3,
                'nome' => 'Competenze bonus',
                'costo' => 'passivo',
                'testo' => 'Armature medie e scimitarra, e le tue armi da bardo possono farti da focus per gli incantesimi: non ti serve più una mano libera per il liuto.',
            ],
            [
                'livello' => 3,
                'nome' => 'Fioretti',
                'costo' => 'passivo',
                'usi' => 'Ognuno consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Quando colpisci con un\'arma puoi aggiungere un fioretto: il Fendente Sferzante colpisce anche un secondo nemico, il Fendente Beffardo dà svantaggio al prossimo tiro del bersaglio, il Fendente Mobile lo spinge via e ti lascia passare.',
            ],
            [
                'livello' => 6,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una.',
            ],
        ],

        'Collegio dei Sussurri' => [
            [
                'livello' => 3,
                'nome' => 'Lame psichiche',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Quando colpisci con un\'arma aggiungi danni psichici: 2d6 al 3º livello, e crescono salendo. Sono le parole che feriscono, e non la lama.',
            ],
            [
                'livello' => 3,
                'nome' => 'Parole velenose',
                'costo' => 'azione',
                'usi' => 'Tante volte quanto il tuo modificatore di Carisma; tornano col riposo lungo.',
                'testo' => 'Un minuto a parlare con qualcuno che ti capisce, e poi un tiro salvezza su Saggezza: se fallisce resta spaventato da te per un\'ora, e non si ricorda perché.',
            ],
            [
                'livello' => 6,
                'nome' => 'Manto dei sussurri',
                'costo' => 'reazione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Quando qualcuno muore vicino a te ne prendi l\'ombra, e per un\'ora puoi indossarla: aspetto, voce, e abbastanza dei suoi ricordi da reggere una conversazione con chi lo conosceva.',
            ],
        ],

        'Collegio dell\'Incanto' => [
            [
                'livello' => 3,
                'nome' => 'Manto d\'ispirazione',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Dai punti ferita temporanei a un gruppo di alleati, e ognuno di loro può usare subito la reazione per spostarsi senza prendersi attacchi d\'opportunità. È il privilegio che salva una ritirata.',
            ],
            [
                'livello' => 3,
                'nome' => 'Esibizione ammaliante',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Un minuto di spettacolo, e chi ti ha ascoltato deve superare un tiro salvezza su Saggezza o restare ammaliato per un\'ora.',
            ],
            [
                'livello' => 6,
                'nome' => 'Manto di maestà',
                'costo' => 'bonus',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Per un minuto hai addosso comando: puoi rilanciarlo ogni turno come azione bonus senza spendere slot, e chi ti guarda ha svantaggio al tiro salvezza.',
            ],
        ],

        'Collegio della Creazione' => [
            [
                'livello' => 3,
                'nome' => 'Scintilla di potenziale',
                'costo' => 'passivo',
                'usi' => 'Fa parte dell\'ispirazione bardica.',
                'testo' => 'Il tuo dado d\'ispirazione diventa una scintilla che gira intorno a chi l\'ha ricevuto, e quando la spende fa qualcosa in più: punti ferita temporanei, danni ai nemici vicini, o un salto più lungo.',
            ],
            [
                'livello' => 3,
                'nome' => 'Esibizione della creazione',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo breve o lungo. Anche spendendo uno slot.',
                'testo' => 'Canti qualcosa e quel qualcosa compare: un oggetto non magico che regge fino a un\'ora. Quanto può valere e quanto può essere grosso dipende dal tuo livello.',
            ],
            [
                'livello' => 6,
                'nome' => 'Esibizione animante',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo. Anche spendendo uno slot di 3º livello o più.',
                'testo' => 'Dai vita a un oggetto grande, che si alza e combatte per te per un\'ora. Ti obbedisce, si muove col tuo turno e attacca quando glielo dici come azione bonus.',
            ],
        ],

        'Collegio dell\'Eloquenza' => [
            [
                'livello' => 3,
                'nome' => 'Lingua d\'argento',
                'costo' => 'passivo',
                'testo' => 'Nelle prove di Persuasione e Inganno un tiro del dado sotto il 10 vale 10: non sbagli mai una parola in pubblico.',
            ],
            [
                'livello' => 3,
                'nome' => 'Parole inquietanti',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso dell\'ispirazione bardica.',
                'testo' => 'Sussurri qualcosa a un nemico entro 18 metri, e al suo prossimo tiro salvezza deve sottrarre il tuo dado d\'ispirazione.',
            ],
            [
                'livello' => 6,
                'nome' => 'Ispirazione infallibile',
                'costo' => 'passivo',
                'testo' => 'Quando un compagno spende il tuo dado d\'ispirazione e fallisce lo stesso, il dado non si consuma: se lo tiene.',
            ],
        ],

        'Dominio della Conoscenza' => [
            [
                'livello' => 1,
                'nome' => 'Benedizioni della conoscenza',
                'costo' => 'passivo',
                'testo' => 'Impari due linguaggi, e scegli due fra Arcano, Storia, Natura e Religione: in quelle il tuo bonus di competenza raddoppia.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: lettura dei pensieri',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Leggi i pensieri di una creatura entro 18 metri per un minuto, se non supera il tiro salvezza su Saggezza. Finché la leggi puoi lanciarle addosso suggestione senza spendere slot.',
            ],
        ],

        'Dominio della Guerra' => [
            [
                'livello' => 1,
                'nome' => 'Sacerdote guerriero',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Quando fai l\'azione di Attacco, tiri un attacco in più come azione bonus. Arrivano anche le armature pesanti e le armi da guerra.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: colpo guidato',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Aggiungi +10 a un tiro per colpire, dopo aver tirato ma prima di sapere l\'esito. Vale anche per un alleato entro 9 metri.',
            ],
        ],

        'Dominio della Tempesta' => [
            [
                'livello' => 1,
                'nome' => 'Ira della tempesta',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo modificatore di Saggezza; tornano col riposo lungo.',
                'testo' => 'Quando qualcuno entro 1,5 metri ti colpisce, gli tiri addosso un fulmine o un tuono: 2d8 danni, con tiro salvezza su Destrezza per dimezzare. Arrivano anche le armature pesanti e le armi da guerra.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: collera distruttiva',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'I danni da fulmine o da tuono di un tuo incantesimo non si tirano: prendi il massimo.',
            ],
        ],

        'Dominio dell\'Inganno' => [
            [
                'livello' => 1,
                'nome' => 'Benedizione dell\'ingannatore',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Tocchi un alleato e per un\'ora ha vantaggio alle prove di Destrezza (Furtività). Su di te non funziona.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: invocare duplicato',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Compare una copia illusoria di te entro 9 metri, che dura un minuto. La sposti come azione bonus, puoi lanciare gli incantesimi come se partissi da lì, e finché ti sta accanto hai vantaggio agli attacchi.',
            ],
        ],

        'Dominio della Luce' => [
            [
                'livello' => 1,
                'nome' => 'Bagliore protettivo',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo modificatore di Saggezza; tornano col riposo lungo.',
                'testo' => 'Quando qualcuno entro 9 metri viene attaccato, gli metti davanti un lampo di luce e chi tira ha svantaggio. Arriva anche il trucchetto luce, in più di quelli che sai già.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: bagliore dell\'alba',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Un lampo di sole per 9 metri intorno a te: 2d10 danni radiosi più il tuo livello da chierico, con tiro salvezza su Costituzione per dimezzare. E ogni oscurità magica sparisce.',
            ],
        ],

        'Dominio della Natura' => [
            [
                'livello' => 1,
                'nome' => 'Discepolo della natura',
                'costo' => 'passivo',
                'testo' => 'Impari un trucchetto da druido, una competenza fra Addestrare Animali, Natura e Sopravvivenza, e sai portare le armature pesanti.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: ammaliare animali e vegetali',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Bestie e vegetali entro 9 metri che ti vedono o ti sentono fanno un tiro salvezza su Saggezza: chi fallisce resta ammaliato per un minuto, o finché non gli fai del male.',
            ],
        ],

        'Dominio della Morte' => [
            [
                'livello' => 1,
                'nome' => 'Maestria nella necromanzia',
                'costo' => 'passivo',
                'testo' => 'I tuoi trucchetti che fanno danni necrotici colpiscono un secondo bersaglio entro 1,5 metri dal primo. Arriva anche la competenza nelle armi da guerra.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: tocco della morte',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Tocchi una creatura e le fai 5d8 danni necrotici più il tuo livello da chierico.',
            ],
        ],

        'Dominio della Forgia' => [
            [
                'livello' => 1,
                'nome' => 'Benedizione del fabbro',
                'costo' => 'passivo',
                'usi' => 'Un oggetto alla volta; si rifà a ogni riposo lungo.',
                'testo' => 'Alla fine di un riposo lungo tocchi un\'arma o un\'armatura: diventa magica e prende +1 al tiro per colpire e ai danni, oppure +1 alla Classe Armatura. Arrivano anche le armature pesanti e gli strumenti da fabbro.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: benedizione dell\'artigiano',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Un\'ora di rito e ti ritrovi in mano un oggetto semplice di metallo — una chiave, una spada, una serratura — purché valga meno di 100 monete d\'oro.',
            ],
        ],

        'Dominio della Tomba' => [
            [
                'livello' => 1,
                'nome' => 'Cerchio della mortalità',
                'costo' => 'passivo',
                'testo' => 'Quando curi qualcuno che è a 0 punti ferita non tiri i dadi: prendi il massimo. E il trucchetto stabilizzare lo lanci come azione bonus, da lontano.',
            ],
            [
                'livello' => 1,
                'nome' => 'Occhi della tomba',
                'costo' => 'azione',
                'usi' => 'Tante volte quanto il tuo modificatore di Saggezza; tornano col riposo lungo.',
                'testo' => 'Senti dove sono i non morti entro 18 metri che non abbiano una copertura totale: dove, che tipo, e niente più.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: cammino verso la tomba',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Marchi una creatura: il prossimo colpo che prende la trova vulnerabile a tutto, cioè con i danni raddoppiati. Poi il marchio svanisce.',
            ],
        ],

        'Dominio dell\'Ordine' => [
            [
                'livello' => 1,
                'nome' => 'Voce dell\'autorità',
                'costo' => 'passivo',
                'testo' => 'Quando lanci un incantesimo di 1º livello o più su un alleato, quello può usare subito la reazione per attaccare una creatura che hai preso di mira. Arrivano anche le armature pesanti e le competenze in Intuizione o Persuasione.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: ordine imperioso',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Le creature entro 9 metri che ti vedono o ti sentono fanno un tiro salvezza su Saggezza: chi fallisce resta affascinato da te fino alla fine del tuo turno successivo, e non può muoversi.',
            ],
        ],

        'Dominio della Pace' => [
            [
                'livello' => 1,
                'nome' => 'Vincolo incoraggiante',
                'costo' => 'azione',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Leghi un gruppo di alleati per dieci minuti: finché due di loro sono entro 9 metri l\'uno dall\'altro, chi tira per colpire, fa una prova o un tiro salvezza aggiunge 1d4.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: balsamo della pace',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Ti muovi senza provocare attacchi d\'opportunità e curi chi passi accanto: 2d6 più il tuo modificatore di Saggezza, una volta a testa.',
            ],
        ],

        'Dominio del Crepuscolo' => [
            [
                'livello' => 1,
                'nome' => 'Occhi del crepuscolo',
                'costo' => 'passivo',
                'testo' => 'Scurovisione fino a 90 metri, e come azione la dai per un\'ora a tutti gli alleati entro 3 metri. Arrivano anche le armature pesanti e le armi da guerra.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: santuario del crepuscolo',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Un\'aura di 9 metri per un minuto. Chi comincia il turno lì dentro sceglie: punti ferita temporanei pari a 1d6 più il tuo livello da chierico, oppure vedersi togliere di dosso la paura o l\'ammaliamento.',
            ],
        ],

        'Dominio Arcano' => [
            [
                'livello' => 1,
                'nome' => 'Trucchetto arcano',
                'costo' => 'passivo',
                'testo' => 'Impari un trucchetto da mago, che per te conta come da chierico.',
            ],
            [
                'livello' => 2,
                'nome' => 'Incanalare divinità: abiurazione arcana',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Celestiali, elementali, fatati e immondi entro 9 metri fanno un tiro salvezza su Saggezza: chi fallisce scappa da te per un minuto. Quelli abbastanza deboli vengono rispediti da dove sono venuti.',
            ],
        ],

        'Circolo della Luna' => [
            [
                'livello' => 2,
                'nome' => 'Forma selvatica da combattimento',
                'costo' => 'bonus',
                'testo' => 'Ti trasformi come azione bonus e non come azione. E mentre sei una bestia puoi spendere uno slot per curarti di 1d8 per livello dello slot.',
            ],
            [
                'livello' => 2,
                'nome' => 'Forme del circolo',
                'costo' => 'passivo',
                'testo' => 'Le bestie che puoi diventare arrivano a grado sfida 1 già dal 2º livello, e poi a un terzo del tuo livello da druido. È il circolo che combatte davvero da orso.',
            ],
            [
                'livello' => 6,
                'nome' => 'Colpo primordiale',
                'costo' => 'passivo',
                'testo' => 'In forma di bestia i tuoi attacchi contano come magici: le resistenze ai danni non magici non ti fermano più.',
            ],
        ],

        'Circolo dei Sogni' => [
            [
                'livello' => 2,
                'nome' => 'Balsamo della Corte d\'Estate',
                'costo' => 'bonus',
                'usi' => 'Una riserva di d6 pari al tuo livello da druido; torna col riposo lungo.',
                'testo' => 'Spendi quanti dadi vuoi su una creatura entro 36 metri: recupera quei punti ferita, più altrettanti temporanei per un dado.',
            ],
            [
                'livello' => 6,
                'nome' => 'Rifugio della Corte d\'Estate',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Un minuto di rito e apri un rifugio invisibile dove il gruppo può riposare: dentro non si è visti né sentiti da fuori, e chi ci passa la notte fa un riposo lungo intero.',
            ],
        ],

        'Circolo del Pastore' => [
            [
                'livello' => 2,
                'nome' => 'Lingua della bestia e della foglia',
                'costo' => 'passivo',
                'testo' => 'Parli con le bestie e con i vegetali, e loro capiscono te. Con la Saggezza alta ti aiutano anche volentieri.',
            ],
            [
                'livello' => 2,
                'nome' => 'Totem spirituale',
                'costo' => 'bonus',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Evochi uno spirito che resta in un\'area di 9 metri per un minuto. Orso: punti ferita temporanei e vantaggio alle prove di Forza. Falco: vantaggio ai tiri per colpire, come reazione. Unicorno: cure più efficaci e vantaggio a trovare le creature.',
            ],
            [
                'livello' => 6,
                'nome' => 'Evocazioni possenti',
                'costo' => 'passivo',
                'testo' => 'Le creature che evochi hanno punti ferita in più pari al tuo livello da druido, e i loro attacchi contano come magici.',
            ],
        ],

        'Circolo delle Spore' => [
            [
                'livello' => 2,
                'nome' => 'Alone di spore',
                'costo' => 'reazione',
                'testo' => 'Hai intorno una nube invisibile di spore per 3 metri: quando qualcuno lì dentro ti dà fastidio, prende 1d4 danni necrotici a meno che non superi un tiro salvezza su Costituzione. Il dado cresce col livello.',
            ],
            [
                'livello' => 2,
                'nome' => 'Simbiosi con i funghi',
                'costo' => 'bonus',
                'usi' => 'Costa un uso della forma selvatica.',
                'testo' => 'Invece di trasformarti, le spore entrano in te: punti ferita temporanei pari a quattro volte il tuo livello da druido, danni necrotici in più con le armi, e vantaggio alle prove e ai tiri salvezza su Costituzione. Dura dieci minuti.',
            ],
            [
                'livello' => 6,
                'nome' => 'Infestazione fungina',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo modificatore di Saggezza; tornano col riposo lungo.',
                'testo' => 'Quando una bestia o un umanoide muore entro 3 metri, si rialza come zombi per un\'ora e fa quello che gli dici.',
            ],
        ],

        'Circolo delle Stelle' => [
            [
                'livello' => 2,
                'nome' => 'Mappa stellare',
                'costo' => 'passivo',
                'usi' => 'Dardo guidato tante volte quanto il tuo bonus di competenza; torna col riposo lungo.',
                'testo' => 'Hai una mappa del cielo che ti fa da focus: conosci sempre dardo guidato, e puoi lanciarlo senza slot. Se la perdi, un\'ora di rito e ne hai un\'altra.',
            ],
            [
                'livello' => 2,
                'nome' => 'Forma stellare',
                'costo' => 'bonus',
                'usi' => 'Costa un uso della forma selvatica.',
                'testo' => 'Invece di diventare una bestia diventi una costellazione, per dieci minuti. Arciere: un attacco a distanza come azione bonus. Calice: le tue cure raggiungono anche un secondo ferito. Drago: nelle prove di concentrazione e in quelle di Intelligenza e Saggezza un tiro sotto il 10 vale 10.',
            ],
            [
                'livello' => 6,
                'nome' => 'Costellazione cosmica',
                'costo' => 'passivo',
                'testo' => 'Quando usi la forma stellare o cominci il tuo turno in quella forma, ti muovi di 9 metri senza spendere movimento.',
            ],
        ],

        'Circolo del Fuoco Selvaggio' => [
            [
                'livello' => 2,
                'nome' => 'Spirito del fuoco selvatico',
                'costo' => 'azione',
                'usi' => 'Costa un uso della forma selvatica.',
                'testo' => 'Evochi uno spirito di fiamma che resta un\'ora e combatte con te: si muove col tuo turno e attacca quando glielo dici come azione bonus. Puoi anche teletrasportarti scambiandoti di posto con lui.',
            ],
            [
                'livello' => 6,
                'nome' => 'Fiamme guaritrici',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Quando tu o un alleato entro 9 metri dallo spirito prendete danni, quello che avete perso torna indietro in parte: 2d6 punti ferita.',
            ],
        ],

        'Maestro di Battaglia' => [
            [
                'livello' => 3,
                'nome' => 'Manovre',
                'costo' => 'passivo',
                'usi' => 'Quattro dadi di superiorità d8; tornano col riposo breve o lungo.',
                'testo' => 'Impari tre manovre — disarmare, sbilanciare, minacciare, colpire per far muovere un alleato — e ognuna costa un dado di superiorità, che si aggiunge anche ai danni. Quali hai preso sta scritto sulla tua scheda di carta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Studioso di guerra',
                'costo' => 'passivo',
                'testo' => 'Diventi competente in uno strumento da artigiano o in un\'abilità a scelta.',
            ],
            [
                'livello' => 7,
                'nome' => 'Conosci il tuo nemico',
                'costo' => 'passivo',
                'testo' => 'Un minuto a guardare qualcuno combattere e sai come sta messo rispetto a te: punti ferita, classe armatura, competenze, immunità.',
            ],
        ],

        'Cavaliere Mistico' => [
            [
                'livello' => 3,
                'nome' => 'Legame con l\'arma',
                'costo' => 'passivo',
                'testo' => 'Un rito di un\'ora e un\'arma è tua: nessuno può disarmartene, e la richiami in mano come azione bonus da qualunque distanza, purché siate sullo stesso piano.',
            ],
            [
                'livello' => 7,
                'nome' => 'Magia da guerra',
                'costo' => 'bonus',
                'testo' => 'Quando lanci un trucchetto, tiri anche un attacco con l\'arma come azione bonus.',
            ],
        ],

        'Cavaliere' => [
            [
                'livello' => 3,
                'nome' => 'Marchio incrollabile',
                'costo' => 'passivo',
                'usi' => 'Il colpo di rappresaglia, tante volte quanto il tuo modificatore di Forza; torna col riposo lungo.',
                'testo' => 'Chi colpisci in mischia resta marchiato fino alla fine del tuo turno successivo: se attacca qualcuno che non sei tu ha svantaggio, e tu puoi inseguirlo con un attacco che fa danni in più.',
            ],
            [
                'livello' => 3,
                'nome' => 'Nato in sella',
                'costo' => 'passivo',
                'testo' => 'Montare e smontare ti costa metà movimento, e se la cavalcatura viene fatta cadere tu resti in piedi con un tiro salvezza su Destrezza.',
            ],
            [
                'livello' => 7,
                'nome' => 'Manovra di protezione',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo modificatore di Costituzione; tornano col riposo lungo.',
                'testo' => 'Quando qualcuno entro 1,5 metri viene colpito, ci metti in mezzo lo scudo: tira 1d8, aggiungilo alla sua Classe Armatura contro quel colpo, e se così basta il colpo manca.',
            ],
        ],

        'Samurai' => [
            [
                'livello' => 3,
                'nome' => 'Spirito combattivo',
                'costo' => 'bonus',
                'usi' => 'Tre volte; tornano col riposo lungo.',
                'testo' => 'Vantaggio a tutti i tuoi attacchi con le armi fino alla fine del turno, e punti ferita temporanei. È la sottoclasse che si rialza e continua.',
            ],
            [
                'livello' => 7,
                'nome' => 'Cortigiano elegante',
                'costo' => 'passivo',
                'testo' => 'Nelle prove di Saggezza (Intuizione) puoi usare la Saggezza al posto del Carisma per persuadere, e diventi competente in Storia, Intuizione, Persuasione o Intrattenere. I tiri salvezza su Saggezza li passi più spesso.',
            ],
        ],

        'Arciere Arcano' => [
            [
                'livello' => 3,
                'nome' => 'Colpo arcano',
                'costo' => 'passivo',
                'usi' => 'Due volte; tornano col riposo breve o lungo.',
                'testo' => 'Impari due colpi che si aggiungono a una freccia già andata a segno: quella che spaventa, quella che rimbalza su un secondo nemico, quella che attraversa i muri, quella che avvelena. Quali hai preso sta sulla tua scheda di carta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Sapiente arcano',
                'costo' => 'passivo',
                'testo' => 'Impari il trucchetto prestidigitazione o individuazione del magico, e diventi competente in Arcano o Natura.',
            ],
            [
                'livello' => 7,
                'nome' => 'Freccia magica',
                'costo' => 'passivo',
                'testo' => 'Ogni freccia che tiri conta come magica.',
            ],
            [
                'livello' => 7,
                'nome' => 'Colpo curvo',
                'costo' => 'reazione',
                'testo' => 'Quando manchi con un attacco a distanza, la freccia curva e la ritiri contro un altro bersaglio entro 18 metri dal primo.',
            ],
        ],

        'Bannereto' => [
            [
                'livello' => 3,
                'nome' => 'Grido di richiamo',
                'costo' => 'passivo',
                'testo' => 'Quando usi Recuperare energie, tre alleati entro 18 metri che ti vedono o ti sentono recuperano punti ferita pari a metà del tuo livello da guerriero.',
            ],
            [
                'livello' => 7,
                'nome' => 'Emissario reale',
                'costo' => 'passivo',
                'testo' => 'Nelle prove di Persuasione il bonus di competenza raddoppia, e impari una lingua. Diventi competente in Storia, Intuizione, Intrattenere o Persuasione.',
            ],
        ],

        'Guerriero Psionico' => [
            [
                'livello' => 3,
                'nome' => 'Colpo potenziato psionicamente',
                'costo' => 'passivo',
                'usi' => 'Costa un dado di energia psionica. Una volta per turno.',
                'testo' => 'Aggiungi il dado ai danni di un attacco andato a segno: sono danni da forza. I dadi tornano uno per riposo breve, e tutti col lungo.',
            ],
            [
                'livello' => 3,
                'nome' => 'Movimento telecinetico',
                'costo' => 'azione',
                'usi' => 'Una volta per riposo, oppure spendendo un dado di energia psionica.',
                'testo' => 'Sposti col pensiero un oggetto o una creatura di taglia Media o meno, di 9 metri, senza toccarli.',
            ],
            [
                'livello' => 3,
                'nome' => 'Scudo protettivo',
                'costo' => 'reazione',
                'usi' => 'Costa un dado di energia psionica.',
                'testo' => 'Quando qualcuno che vedi entro 9 metri prende danni, gliene togli il tiro del dado più il tuo modificatore di Intelligenza.',
            ],
            [
                'livello' => 7,
                'nome' => 'Adepto telecinetico',
                'costo' => 'passivo',
                'testo' => 'Il movimento telecinetico diventa due cose: uno spostamento istantaneo di te stesso di 9 metri, oppure una spinta che butta indietro una creatura.',
            ],
        ],

        'Cavaliere Runico' => [
            [
                'livello' => 3,
                'nome' => 'Rune',
                'costo' => 'passivo',
                'usi' => 'Ogni runa ha il suo uso, e torna col riposo breve o lungo.',
                'testo' => 'Incidi due rune giganti sul tuo equipaggiamento: ognuna dà un vantaggio sempre attivo — vantaggio a certe prove, resistenza a un danno — e un potere che si invoca una volta. Quali hai preso sta sulla tua scheda di carta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Conoscenza delle rune',
                'costo' => 'passivo',
                'testo' => 'Impari il gigante, e diventi competente negli strumenti da fabbro.',
            ],
            [
                'livello' => 7,
                'nome' => 'Potenza del gigante',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Diventi di taglia Grande per un minuto: vantaggio alle prove e ai tiri salvezza su Forza, e un dado di danno in più una volta per turno.',
            ],
        ],

        'Cavaliere dell\'Eco' => [
            [
                'livello' => 3,
                'nome' => 'Manifestare eco',
                'costo' => 'bonus',
                'testo' => 'Fai comparire entro 9 metri una copia spettrale di te. Ha la tua Classe Armatura, un punto ferita, e puoi attaccare partendo da lì invece che da dove sei tu.',
            ],
            [
                'livello' => 3,
                'nome' => 'Balzo dell\'eco',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Ti scambi di posto con la tua eco, all\'istante. È il modo di attraversare una stanza senza attraversarla.',
            ],
            [
                'livello' => 7,
                'nome' => 'Avatar dell\'eco',
                'costo' => 'azione',
                'testo' => 'Per dieci minuti vedi e senti attraverso la tua eco, che intanto può allontanarsi fino a un chilometro e mezzo. Tu resti dove sei, cieco e sordo del tuo.',
            ],
        ],

        'Assassino' => [
            [
                'livello' => 3,
                'nome' => 'Assassinare',
                'costo' => 'passivo',
                'testo' => 'Nel primo round hai vantaggio contro chiunque non abbia ancora agito, e ogni colpo contro qualcuno colto di sorpresa è un critico. È il privilegio che rende il primo round tutto.',
            ],
            [
                'livello' => 3,
                'nome' => 'Competenze bonus',
                'costo' => 'passivo',
                'testo' => 'Kit da trucco e arnesi da avvelenatore.',
            ],
            [
                'livello' => 9,
                'nome' => 'Identità infallibili',
                'costo' => 'passivo',
                'testo' => 'Nessuno smaschera un tuo travestimento con una prova, e per magia serve almeno un incantesimo di 6º livello. Ti costruisci identità false che reggono.',
            ],
        ],

        'Furfante Arcano' => [
            [
                'livello' => 3,
                'nome' => 'Mano magica potenziata',
                'costo' => 'passivo',
                'testo' => 'Conosci mano magica, la mano è invisibile e con quella puoi rubare, aprire serrature e disinnescare trappole a distanza — usando la tua Destrezza (Rapidità di mano).',
            ],
            [
                'livello' => 9,
                'nome' => 'Imboscata magica',
                'costo' => 'passivo',
                'testo' => 'Se lanci un incantesimo mentre sei nascosto, chi lo subisce ha svantaggio al tiro salvezza.',
            ],
        ],

        'Mente Superiore' => [
            [
                'livello' => 3,
                'nome' => 'Maestro di tattica',
                'costo' => 'bonus',
                'testo' => 'L\'azione di Aiutare la fai come azione bonus, e puoi aiutare qualcuno ad attaccare un nemico entro 9 metri da te invece che 1,5.',
            ],
            [
                'livello' => 3,
                'nome' => 'Maestro dell\'intrigo',
                'costo' => 'passivo',
                'testo' => 'Kit da trucco, un set di giochi, due lingue. E sai imitare la parlata di chiunque tu abbia ascoltato per un\'ora.',
            ],
            [
                'livello' => 9,
                'nome' => 'Manipolatore perspicace',
                'costo' => 'passivo',
                'testo' => 'Un minuto a osservare qualcuno e sai se ti è superiore o inferiore in Intelligenza, Saggezza, Carisma o livelli di classe.',
            ],
        ],

        'Spadaccino' => [
            [
                'livello' => 3,
                'nome' => 'Gioco di gambe',
                'costo' => 'passivo',
                'testo' => 'Chi colpisci in mischia non può tirarti l\'attacco d\'opportunità fino alla fine del turno. Entri ed esci dalla mischia senza spendere niente.',
            ],
            [
                'livello' => 3,
                'nome' => 'Audacia sfrontata',
                'costo' => 'passivo',
                'testo' => 'Aggiungi il modificatore di Carisma all\'iniziativa, e ti guadagni l\'attacco furtivo anche solo stando da solo contro uno, senza bisogno di vantaggio né di alleati vicini.',
            ],
            [
                'livello' => 9,
                'nome' => 'Sfrontatezza',
                'costo' => 'azione',
                'testo' => 'Una prova di Carisma (Persuasione) contro la Saggezza di qualcuno: se vinci, per un minuto ti viene dietro e ha svantaggio contro chiunque non sia te. Fuori dal combattimento, per un\'ora ti considera un conoscente amichevole.',
            ],
        ],

        'Esploratore' => [
            [
                'livello' => 3,
                'nome' => 'Schermagliatore',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno finisce il turno entro 1,5 metri da te, ti sposti di metà velocità senza provocare attacchi d\'opportunità.',
            ],
            [
                'livello' => 3,
                'nome' => 'Sopravvissuto',
                'costo' => 'passivo',
                'testo' => 'Competenza in Natura e Sopravvivenza, e in quelle due il bonus di competenza raddoppia.',
            ],
            [
                'livello' => 9,
                'nome' => 'Mobilità superiore',
                'costo' => 'passivo',
                'testo' => 'La tua velocità aumenta di 3 metri, e se sai nuotare o arrampicarti quelle velocità crescono con lei.',
            ],
        ],

        'Inquisitore' => [
            [
                'livello' => 3,
                'nome' => 'Combattimento perspicace',
                'costo' => 'bonus',
                'testo' => 'Una prova di Saggezza (Intuizione) contro l\'Inganno di qualcuno: se vinci, per un minuto contro di lui hai l\'attacco furtivo anche senza vantaggio e senza alleati vicini.',
            ],
            [
                'livello' => 3,
                'nome' => 'Occhio per il dettaglio',
                'costo' => 'bonus',
                'testo' => 'Cerchi indizi o noti creature nascoste come azione bonus, invece che come azione.',
            ],
            [
                'livello' => 3,
                'nome' => 'Orecchio per l\'inganno',
                'costo' => 'passivo',
                'testo' => 'Nelle prove di Saggezza (Intuizione) per capire se ti stanno mentendo, un tiro sotto il 8 vale 8.',
            ],
            [
                'livello' => 9,
                'nome' => 'Sguardo fermo',
                'costo' => 'passivo',
                'testo' => 'Vantaggio a Percezione e Indagare, se in quel turno ti muovi al massimo a metà velocità.',
            ],
        ],

        'Fantasma' => [
            [
                'livello' => 3,
                'nome' => 'Lamenti dalla tomba',
                'costo' => 'passivo',
                'usi' => 'Metà del tuo bonus di competenza, arrotondata per eccesso; tornano col riposo lungo.',
                'testo' => 'Quando fai un attacco furtivo, metà di quei dadi li fa anche a un secondo bersaglio entro 9 metri dal primo: danni necrotici, senza tirare per colpire.',
            ],
            [
                'livello' => 3,
                'nome' => 'Sussurri dei morti',
                'costo' => 'passivo',
                'testo' => 'Dopo un riposo breve o lungo prendi in prestito la competenza di qualcuno che è morto: un\'abilità o uno strumento, a scelta, finché non ne prendi un\'altra.',
            ],
            [
                'livello' => 9,
                'nome' => 'Pegni dei defunti',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno muore vicino a te ne raccogli un\'anima in un pegno. I pegni si spendono per ripetere un tiro salvezza, per tirare un dado in più all\'attacco furtivo, o per fare una domanda a chi è morto.',
            ],
        ],

        'Lama dell\'Anima' => [
            [
                'livello' => 3,
                'nome' => 'Lame psichiche',
                'costo' => 'passivo',
                'testo' => 'Fai comparire dal nulla una lama di pura energia mentale, come parte dell\'attacco: è un\'arma con finezza, si tira anche a distanza, e sparisce subito dopo. Non te la può togliere nessuno, perché non ce l\'hai mai in mano.',
            ],
            [
                'livello' => 3,
                'nome' => 'Dadi di energia psionica',
                'costo' => 'passivo',
                'usi' => 'Una riserva di dadi: uno torna col riposo breve, tutti col lungo.',
                'testo' => 'Alimentano i sussurri psionici — parlare nella mente di qualcuno — e il colpo del veggente, che aggiunge un dado a una prova andata male.',
            ],
            [
                'livello' => 9,
                'nome' => 'Velo psichico',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo. Oppure spendendo un dado di energia psionica.',
                'testo' => 'Diventi invisibile per un\'ora, finché non attacchi o non costringi qualcuno a un tiro salvezza.',
            ],
        ],

        'Abiurazione' => [
            [
                'livello' => 2,
                'nome' => 'Guardia arcana',
                'costo' => 'passivo',
                'usi' => 'Si ricarica lanciando un incantesimo di abiurazione; torna intera col riposo lungo.',
                'testo' => 'Ti circondi di uno scudo invisibile con punti ferita pari al doppio del tuo livello da mago più il modificatore di Intelligenza: assorbe i danni al posto tuo, e si ricarica ogni volta che lanci un\'abiurazione.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente dell\'abiurazione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di abiurazione nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Protezione proiettata',
                'costo' => 'reazione',
                'testo' => 'Quando un alleato entro 9 metri prende danni, la tua guardia arcana ne assorbe metà — e quella metà la paga lei.',
            ],
        ],

        'Ammaliamento' => [
            [
                'livello' => 2,
                'nome' => 'Sguardo ipnotico',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Fissi negli occhi una creatura entro 1,5 metri: se non supera il tiro salvezza su Saggezza resta affascinata, stordita, e la sua velocità diventa zero. Puoi tenerla lì di turno in turno, finché continui a guardarla.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente dell\'ammaliamento',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di ammaliamento nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Fascino istintivo',
                'costo' => 'reazione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Quando qualcuno entro 9 metri attacca te, lo convinci a colpire qualcun altro: tiro salvezza su Saggezza, e se fallisce sceglie un altro bersaglio.',
            ],
        ],

        'Divinazione' => [
            [
                'livello' => 2,
                'nome' => 'Presagio',
                'costo' => 'passivo',
                'usi' => 'Due volte al giorno; si tirano dopo il riposo lungo.',
                'testo' => 'Dopo un riposo lungo tiri due d20 e li tieni da parte. Quando vuoi, uno di quei due risultati **sostituisce** un tiro tuo o di chiunque altro, anche di un nemico. È il privilegio che decide un combattimento prima che cominci.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente della divinazione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di divinazione nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Esperto della divinazione',
                'costo' => 'passivo',
                'testo' => 'Quando lanci un incantesimo di divinazione di 2º livello o più, ti torna indietro uno slot di livello più basso.',
            ],
        ],

        'Evocazione' => [
            [
                'livello' => 2,
                'nome' => 'Evocazione minore',
                'costo' => 'azione',
                'testo' => 'Fai comparire un oggetto non magico che ci starebbe in un cubo di 90 centimetri e pesa meno di cinque chili. Dura un\'ora, o finché non lo usi per colpire qualcuno.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente dell\'evocazione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di evocazione nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Trasposizione benefica',
                'costo' => 'passivo',
                'testo' => 'Il tuo teletrasporto di 9 metri può portare con te un alleato, o scambiarti di posto con lui.',
            ],
        ],

        'Illusione' => [
            [
                'livello' => 2,
                'nome' => 'Illusione minore migliorata',
                'costo' => 'passivo',
                'testo' => 'Conosci illusione minore, e puoi farne una di suono e una di immagine insieme, con la stessa azione.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente dell\'illusione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di illusione nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Illusioni malleabili',
                'costo' => 'azione',
                'testo' => 'Cambi da capo una tua illusione già in corso, purché duri almeno un minuto: era una porta, adesso è un muro.',
            ],
        ],

        'Necromanzia' => [
            [
                'livello' => 2,
                'nome' => 'Raccolto crudele',
                'costo' => 'passivo',
                'testo' => 'Quando uccidi qualcuno con un incantesimo di 1º livello o più, recuperi punti ferita pari al doppio del livello dello slot — il triplo se era un non morto.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente della necromanzia',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di necromanzia nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Servitori non morti',
                'costo' => 'passivo',
                'testo' => 'Animare morti ti rianima una creatura in più, e ognuna è più forte: punti ferita in più pari al tuo livello da mago, e il tuo bonus di competenza ai danni.',
            ],
        ],

        'Trasmutazione' => [
            [
                'livello' => 2,
                'nome' => 'Piccoli trucchi',
                'costo' => 'azione',
                'testo' => 'Cambi il colore o l\'odore di una cosa, accendi o spegni una fiammella, fai maturare un frutto, disegni un\'immagine su una superficie, fai schiudere un uovo. Sciocchezze, che risolvono più situazioni di quanto sembri.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente della trasmutazione',
                'costo' => 'passivo',
                'testo' => 'Copiare un incantesimo di trasmutazione nel libro costa metà tempo e metà soldi.',
            ],
            [
                'livello' => 6,
                'nome' => 'Pietra del trasmutatore',
                'costo' => 'passivo',
                'testo' => 'Otto ore di lavoro e hai una pietra che dai a chi vuoi: dà scurovisione, o velocità in più, o competenza nei tiri salvezza su Costituzione, o resistenza a un elemento. Il beneficio si cambia ogni volta che lanci una trasmutazione.',
            ],
        ],

        'Magia da Guerra' => [
            [
                'livello' => 2,
                'nome' => 'Deviazione arcana',
                'costo' => 'reazione',
                'testo' => 'Quando ti colpiscono o fallisci un tiro salvezza, alzi di 2 la Classe Armatura o di 4 il tiro salvezza — e per il resto del turno lanci solo trucchetti.',
            ],
            [
                'livello' => 2,
                'nome' => 'Guerriero tattico',
                'costo' => 'passivo',
                'testo' => 'Aggiungi il modificatore di Intelligenza all\'iniziativa: sei il mago che agisce per primo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Sovraccarico di potere',
                'costo' => 'passivo',
                'usi' => 'Due volte; tornano col riposo breve o lungo.',
                'testo' => 'Dopo aver lanciato un incantesimo di 1º livello o più, il tuo prossimo dardo incantato fa danni in più pari al modificatore di Intelligenza — a ogni dardo.',
            ],
        ],

        'Canto di Lama' => [
            [
                'livello' => 2,
                'nome' => 'Canto di lama',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo breve o lungo.',
                'testo' => 'Per un minuto: la Classe Armatura sale del tuo modificatore di Intelligenza, la velocità di 3 metri, hai vantaggio alle prove di Destrezza (Acrobazia) e sommi l\'Intelligenza ai tiri di concentrazione. Non funziona in armatura o con lo scudo.',
            ],
            [
                'livello' => 2,
                'nome' => 'Addestramento nel canto di lama',
                'costo' => 'passivo',
                'testo' => 'Competenza nelle armature leggere e in un\'arma da guerra con finezza, più una competenza in Intrattenere.',
            ],
            [
                'livello' => 6,
                'nome' => 'Attacco extra',
                'costo' => 'passivo',
                'testo' => 'Quando fai l\'azione di Attacco, attacchi due volte invece di una — e uno dei due attacchi può essere un trucchetto.',
            ],
        ],

        'Cronurgia' => [
            [
                'livello' => 2,
                'nome' => 'Scarto temporale',
                'costo' => 'reazione',
                'da_controllare' => true,
                'usi' => 'Due volte; tornano col riposo lungo.',
                'testo' => 'Riavvolgi un attimo: chiunque abbia appena tirato per colpire, fatto una prova o un tiro salvezza lo rifà, e vale il secondo risultato. Vale per chiunque, anche per un nemico.',
            ],
            [
                'livello' => 2,
                'nome' => 'Sapiente temporale',
                'costo' => 'passivo',
                'da_controllare' => true,
                'testo' => 'Aggiungi il modificatore di Intelligenza all\'iniziativa.',
            ],
            [
                'livello' => 6,
                'nome' => 'Stasi momentanea',
                'costo' => 'azione',
                'da_controllare' => true,
                'usi' => 'Tante volte quanto il tuo modificatore di Intelligenza; tornano col riposo lungo.',
                'testo' => 'Fermi il tempo intorno a una creatura di taglia Grande o meno: se non supera il tiro salvezza su Costituzione resta paralizzata fino alla fine del tuo turno successivo, o finché non prende danni.',
            ],
        ],

        'Graviturgia' => [
            [
                'livello' => 2,
                'nome' => 'Regolare densità',
                'costo' => 'azione',
                'da_controllare' => true,
                'testo' => 'Con la concentrazione, alleggerisci o appesantisci qualcosa: chi diventa leggero va più veloce ma è debole, chi diventa pesante rallenta e regge meglio le prove di Forza.',
            ],
            [
                'livello' => 6,
                'nome' => 'Pozzo gravitazionale',
                'costo' => 'passivo',
                'da_controllare' => true,
                'testo' => 'Ogni volta che un tuo incantesimo fa danni o cura, puoi anche spostare di 1,5 metri chi ha colpito.',
            ],
        ],

        'Ordine degli Scribi' => [
            [
                'livello' => 2,
                'nome' => 'Libro degli incantesimi risvegliato',
                'costo' => 'passivo',
                'usi' => 'Il cambio di tipo di danno, una volta per riposo lungo.',
                'testo' => 'Il tuo libro si sveglia e parla. Puoi lanciare come rituale qualunque incantesimo che ci sia scritto, e una volta al giorno cambiare il tipo di danno di un incantesimo con quello di un altro che hai nel libro.',
            ],
            [
                'livello' => 2,
                'nome' => 'Penna magica',
                'costo' => 'passivo',
                'testo' => 'Una penna che non consuma inchiostro: copiare gli incantesimi costa la metà, e scrivi in qualunque lingua tu conosca, imitando qualunque calligrafia tu abbia visto.',
            ],
            [
                'livello' => 6,
                'nome' => 'Mente manifesta',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; torna col riposo lungo.',
                'testo' => 'Lo spirito del libro esce e diventa una figura luminosa che vaga per te: vedi e senti attraverso di lei, puoi lanciare gli incantesimi come se partissero da lì, e aggiunge il tuo modificatore di Intelligenza ai danni o alle cure.',
            ],
        ],

        'Via dell\'Ombra' => [
            [
                'livello' => 3,
                'nome' => 'Arti dell\'ombra',
                'costo' => 'passivo',
                'usi' => 'Ogni incantesimo costa 2 punti ki. Il trucchetto è gratis.',
                'testo' => 'Conosci il trucchetto illusione minore, e puoi lanciare oscurità, scurovisione, passo senza tracce e silenzio spendendo ki invece degli slot.',
            ],
            [
                'livello' => 6,
                'nome' => 'Passo dell\'ombra',
                'costo' => 'bonus',
                'testo' => 'Se sei in penombra o al buio, ti teletrasporti fino a 18 metri in un altro punto in ombra — e il primo attacco che fai subito dopo ha vantaggio.',
            ],
        ],

        'Via dei Quattro Elementi' => [
            [
                'livello' => 3,
                'nome' => 'Discepolo degli elementi',
                'costo' => 'passivo',
                'usi' => 'Ogni disciplina ha il suo costo in punti ki.',
                'testo' => 'Impari discipline elementali: soffio di drago, pugno del vento, onda d\'acqua, artigli di fuoco. Sono il modo del monaco di lanciare incantesimi spendendo ki. Ne impari altre al 6º, 11º e 17º livello, e quali hai preso sta sulla tua scheda di carta.',
            ],
            [
                'livello' => 6,
                'nome' => 'Un\'altra disciplina',
                'costo' => 'passivo',
                'testo' => 'Impari una disciplina elementale in più, e puoi sostituirne una che avevi già.',
            ],
        ],

        'Via dell\'Anima Solare' => [
            [
                'livello' => 3,
                'nome' => 'Dardo di sole radiante',
                'costo' => 'passivo',
                'usi' => 'Il dardo in più come azione bonus costa 1 punto ki.',
                'testo' => 'Tiri raggi di luce a distanza come se fossero colpi senz\'armi: 1d4 danni radiosi che crescono con le arti marziali, fino a 30 metri. Spendendo ki ne tiri due in più come azione bonus.',
            ],
            [
                'livello' => 6,
                'nome' => 'Colpo dell\'arco ardente',
                'costo' => 'bonus',
                'usi' => 'Costa 2 punti ki.',
                'testo' => 'Subito dopo aver fatto l\'azione di Attacco, lanci mani brucianti come azione bonus: spendendo altro ki lo lanci a livelli più alti.',
            ],
        ],

        'Via del Kensei' => [
            [
                'livello' => 3,
                'nome' => 'Cammino del kensei',
                'costo' => 'passivo',
                'testo' => 'Scegli due armi che diventano armi da monaco a tutti gli effetti. Con una in mano hai +2 alla Classe Armatura se non attacchi con quella, e dopo un attacco a distanza il colpo successivo fa 1d4 danni in più.',
            ],
            [
                'livello' => 6,
                'nome' => 'Colpo magico del kensei',
                'costo' => 'passivo',
                'usi' => 'Il dado in più costa 1 punto ki, una volta per turno.',
                'testo' => 'I tuoi colpi senz\'armi contano come magici, e spendendo ki aggiungi un dado di danno a un colpo con l\'arma del kensei.',
            ],
        ],

        'Via del Maestro Ebbro' => [
            [
                'livello' => 3,
                'nome' => 'Tecnica dell\'ubriaco',
                'costo' => 'passivo',
                'testo' => 'Ogni volta che fai una raffica di colpi disingaggi in automatico, e per quel turno la tua velocità cresce di 3 metri. Barcolli, e non ti prende nessuno.',
            ],
            [
                'livello' => 6,
                'nome' => 'Ondeggiare ubriaco',
                'costo' => 'reazione',
                'testo' => 'Ti rialzi da terra spendendo solo 1,5 metri di movimento. E quando qualcuno ti manca, lo convinci a colpire qualcun altro: se supera un tiro salvezza su Destrezza no, altrimenti sì.',
            ],
        ],

        'Via della Lunga Morte' => [
            [
                'livello' => 3,
                'nome' => 'Tocco della morte',
                'costo' => 'passivo',
                'testo' => 'Quando qualcuno muore entro 1,5 metri da te, ne raccogli la fine: punti ferita temporanei pari al tuo livello da monaco più il modificatore di Saggezza.',
            ],
            [
                'livello' => 6,
                'nome' => 'Ora della mietitura',
                'costo' => 'azione',
                'testo' => 'Tutte le creature entro 9 metri che ti vedono fanno un tiro salvezza su Saggezza: chi fallisce resta spaventato fino alla fine del tuo turno successivo.',
            ],
        ],

        'Via della Misericordia' => [
            [
                'livello' => 3,
                'nome' => 'Mano che guarisce',
                'costo' => 'azione',
                'usi' => 'Costa 1 punto ki.',
                'testo' => 'Tocchi una creatura e le ridai punti ferita pari a un tiro dei tuoi dadi delle arti marziali più il modificatore di Saggezza. Spendendo un altro punto ki, chi è a 0 punti ferita si stabilizza.',
            ],
            [
                'livello' => 3,
                'nome' => 'Mano che ferisce',
                'costo' => 'passivo',
                'usi' => 'Costa 1 punto ki. Una volta per turno.',
                'testo' => 'La stessa mano, girata: quando colpisci con un colpo senz\'armi aggiungi danni necrotici pari a un tiro dei dadi delle arti marziali più il modificatore di Saggezza.',
            ],
            [
                'livello' => 6,
                'nome' => 'Tocco del medico',
                'costo' => 'passivo',
                'testo' => 'La mano che guarisce toglie anche una condizione: accecato, assordato, paralizzato, avvelenato, stordito. Quella che ferisce può invece avvelenare.',
            ],
        ],

        'Via del Sé Astrale' => [
            [
                'livello' => 3,
                'nome' => 'Braccia del sé astrale',
                'costo' => 'bonus',
                'usi' => 'Costa 1 punto ki; durano dieci minuti.',
                'testo' => 'Ti compaiono due braccia spettrali. Attacchi con la Saggezza al posto di Destrezza e Forza, i colpi arrivano a 3 metri e fanno danni da forza, e come reazione le braccia colpiscono chi ti si avvicina.',
            ],
            [
                'livello' => 6,
                'nome' => 'Volto del sé astrale',
                'costo' => 'bonus',
                'testo' => 'Compare anche il volto: vedi al buio fino a 36 metri, hai vantaggio alle prove di Intuizione e Intimidire, e chi ti sta entro 3 metri quando lo guardi prende danni psichici.',
            ],
        ],

        'Giuramento degli Antichi' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: ira della natura',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Rampicanti spettrali afferrano chi ti sta entro 3 metri: tiro salvezza su Forza o Destrezza, e chi fallisce resta trattenuto finché non si libera.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: scacciare gli infedeli',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Fatati e immondi entro 9 metri fanno un tiro salvezza su Saggezza: chi fallisce scappa da te per un minuto.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di custodia',
                'costo' => 'passivo',
                'testo' => 'Tu e gli alleati entro 3 metri avete resistenza ai danni degli incantesimi. Il raggio arriva a 9 metri al 18º livello.',
            ],
        ],

        'Giuramento di Vendetta' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: astio del giuramento',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Marchi una creatura e per un minuto hai vantaggio a ogni attacco contro di lei. Se scende a 0 punti ferita prima, il marchio passa a un\'altra.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: scacciare i malvagi',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Immondi e non morti entro 9 metri fanno un tiro salvezza su Saggezza: chi fallisce scappa da te per un minuto.',
            ],
            [
                'livello' => 7,
                'nome' => 'Segugio implacabile',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno che vedi si allontana da te, gli vai dietro fino a metà della tua velocità, restandogli entro 1,5 metri. Non ti si scappa.',
            ],
        ],

        'Giuramento della Corona' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: sfida del campione',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Sfidi le creature entro 9 metri: chi fallisce il tiro salvezza su Saggezza non può allontanarsi da te più di 9 metri. È il modo di tenere i nemici lontani dal resto del gruppo.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: ribaltare le sorti',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Ogni alleato entro 9 metri che sia a più di 0 punti ferita ne recupera 1d6 più il tuo modificatore di Carisma.',
            ],
            [
                'livello' => 7,
                'nome' => 'Lealtà divina',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno entro 1,5 metri sta per prendere danni, li prendi tu al posto suo. Tutti, e senza resistenze.',
            ],
        ],

        'Giuramento della Conquista' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: presenza conquistatrice',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Le creature entro 9 metri fanno un tiro salvezza su Saggezza: chi fallisce resta spaventato per un minuto.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: colpo guidato',
                'costo' => 'passivo',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Aggiungi +10 a un tiro per colpire, dopo aver tirato ma prima di sapere l\'esito.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di conquista',
                'costo' => 'passivo',
                'testo' => 'Chi è spaventato e ti sta entro 3 metri ha velocità zero e prende danni psichici pari a metà del tuo livello da paladino. Il raggio arriva a 9 metri al 18º livello.',
            ],
        ],

        'Giuramento della Redenzione' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: emissario di pace',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Per dieci minuti hai +5 alle prove di Carisma (Persuasione). È il paladino che prova a non combattere.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: placare le fiamme',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Una creatura entro 9 metri fa un tiro salvezza su Saggezza: se fallisce, per un minuto non può attaccare nessuno tranne te, e finisce se qualcun altro le fa del male.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di guardia',
                'costo' => 'passivo',
                'testo' => 'Quando un alleato entro 3 metri prende danni, ne prendi al posto suo una parte pari a metà del tuo livello da paladino. Il raggio arriva a 9 metri al 18º livello.',
            ],
        ],

        'Giuramento della Gloria' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: impresa eroica',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Per dieci minuti tu e gli alleati entro 9 metri aggiungete 1d8 a ogni prova di Atletica e di Acrobazia.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: slancio glorioso',
                'costo' => 'bonus',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'La tua velocità e quella di un massimo di quattro alleati entro 9 metri crescono di 3 metri per un minuto, e nessuno di voi provoca attacchi d\'opportunità.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di allerta',
                'costo' => 'passivo',
                'testo' => 'Tu e gli alleati entro 3 metri aggiungete il tuo bonus di competenza all\'iniziativa. Il raggio arriva a 9 metri al 18º livello.',
            ],
        ],

        'Giuramento dei Guardiani' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: sguardo del vigilante',
                'costo' => 'azione',
                'da_controllare' => true,
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Marchi una creatura entro 9 metri per un minuto: se prova ad attaccare qualcuno che non sei tu, deve superare un tiro salvezza su Saggezza o perdere l\'attacco.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: colpo del vigilante',
                'costo' => 'passivo',
                'da_controllare' => true,
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'I tuoi attacchi con l\'arma fanno danni radiosi in più, per un minuto.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura del guardiano',
                'costo' => 'passivo',
                'da_controllare' => true,
                'testo' => 'Quando un alleato entro 3 metri prende danni, puoi prenderne una parte al posto suo.',
            ],
        ],

        'Spezzagiuramenti' => [
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: controllare i non morti',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Un non morto entro 9 metri con grado sfida non superiore al tuo livello fa un tiro salvezza su Saggezza: se fallisce ti obbedisce per ventiquattro ore.',
            ],
            [
                'livello' => 3,
                'nome' => 'Incanalare divinità: vista infernale',
                'costo' => 'azione',
                'usi' => 'Consuma un uso di Incanalare divinità.',
                'testo' => 'Per un\'ora vedi al buio fino a 36 metri, anche attraverso l\'oscurità magica.',
            ],
            [
                'livello' => 7,
                'nome' => 'Aura di odio',
                'costo' => 'passivo',
                'testo' => 'Tu, i non morti e gli immondi entro 3 metri aggiungete il tuo modificatore di Carisma ai danni degli attacchi in mischia. Il raggio arriva a 9 metri al 18º livello.',
            ],
        ],

        'Signore delle Bestie' => [
            [
                'livello' => 3,
                'nome' => 'Compagno animale',
                'costo' => 'passivo',
                'testo' => 'Una bestia ti sceglie e resta con te: si muove nel tuo turno, e quando fai l\'azione di Attacco puoi rinunciare a un tuo attacco per farla attaccare. Se muore, otto ore di rito e ne arriva un\'altra.',
            ],
            [
                'livello' => 7,
                'nome' => 'Addestramento eccezionale',
                'costo' => 'bonus',
                'testo' => 'Nei turni in cui il compagno non attacca, gli fai fare Scattare, Disingaggiare, Schivare o Aiutare come azione bonus. E i suoi attacchi contano come magici.',
            ],
        ],

        'Vagabondo Fatato' => [
            [
                'livello' => 3,
                'nome' => 'Benedizione della Corte d\'Inverno',
                'costo' => 'passivo',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Una volta per turno aggiungi 1d4 danni psichici a un colpo, e ne recuperi altrettanti punti ferita. Conosci anche marchio del cacciatore, che non conta fra i tuoi incantesimi noti.',
            ],
            [
                'livello' => 7,
                'nome' => 'Passi fatati',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Ti teletrasporti di 9 metri, e chi ti sta vicino quando arrivi si ritrova in svantaggio al prossimo attacco.',
            ],
        ],

        'Cacciatore nell\'Ombra' => [
            [
                'livello' => 3,
                'nome' => 'Agguato temuto',
                'costo' => 'passivo',
                'testo' => 'Aggiungi il modificatore di Saggezza all\'iniziativa, e nel primo turno di ogni combattimento hai 3 metri di velocità in più e un attacco extra che fa 1d8 danni psichici. È la sottoclasse che vince il primo round.',
            ],
            [
                'livello' => 3,
                'nome' => 'Vista d\'ombra',
                'costo' => 'passivo',
                'testo' => 'Vedi al buio fino a 27 metri, e nell\'oscurità sei invisibile a chiunque ci veda solo con la scurovisione.',
            ],
            [
                'livello' => 7,
                'nome' => 'Mente di ferro',
                'costo' => 'passivo',
                'testo' => 'Diventi competente nei tiri salvezza su Saggezza — o su Intelligenza o Carisma, se già li avevi.',
            ],
        ],

        'Viandante dell\'Orizzonte' => [
            [
                'livello' => 3,
                'nome' => 'Individuare portale',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Senti se c\'è un portale planare entro un chilometro e mezzo, e dove porta.',
            ],
            [
                'livello' => 3,
                'nome' => 'Arma planare',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Per un minuto la tua arma conta come magica e fa 1d8 danni da forza in più a ogni colpo.',
            ],
            [
                'livello' => 7,
                'nome' => 'Passo etereo',
                'costo' => 'bonus',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Lanci passo velato su te stesso senza spendere slot: attraversi un muro e sei dall\'altra parte.',
            ],
        ],

        'Sterminatore di Mostri' => [
            [
                'livello' => 3,
                'nome' => 'Sensi del cacciatore',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo modificatore di Saggezza; tornano col riposo lungo.',
                'testo' => 'Tocchi una creatura con i sensi e vieni a sapere cos\'ha di speciale: immunità, resistenze, e che poteri sa usare.',
            ],
            [
                'livello' => 3,
                'nome' => 'Preda dello sterminatore',
                'costo' => 'bonus',
                'testo' => 'Marchi una creatura per un minuto: una volta per turno le fai 1d6 danni in più, e quando prova a colpirti o a lanciarti un incantesimo addosso hai vantaggio al tiro salvezza.',
            ],
            [
                'livello' => 7,
                'nome' => 'Schivata soprannaturale',
                'costo' => 'reazione',
                'testo' => 'Quando qualcuno che vedi ti colpisce, dimezzi i danni di quel colpo.',
            ],
        ],

        'Custode di Sciami' => [
            [
                'livello' => 3,
                'nome' => 'Sciame raccolto',
                'costo' => 'passivo',
                'da_controllare' => true,
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Uno sciame di creaturine ti segue e interviene quando colpisci: le fa 1d6 danni in più, oppure sposta il bersaglio, oppure sposta te di 1,5 metri.',
            ],
            [
                'livello' => 7,
                'nome' => 'Movimento dello sciame',
                'costo' => 'passivo',
                'da_controllare' => true,
                'testo' => 'Lo sciame ti porta: quando usi lo sciame raccolto puoi anche muoverti senza provocare attacchi d\'opportunità.',
            ],
        ],

        'Magia Selvaggia' => [
            [
                'livello' => 1,
                'nome' => 'Ondata di magia selvaggia',
                'costo' => 'passivo',
                'testo' => 'Ogni volta che lanci un incantesimo di 1º livello o più, il dungeon master può farti tirare un d20: con un 1 succede qualcosa dalla tabella del caos, che nessuno dei due ha scelto.',
            ],
            [
                'livello' => 1,
                'nome' => 'Marea del caos',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Vantaggio a un tiro per colpire, a una prova o a un tiro salvezza — al prezzo che il dungeon master può farti tirare sulla tabella del caos alla prossima magia.',
            ],
            [
                'livello' => 6,
                'nome' => 'Curvare la fortuna',
                'costo' => 'reazione',
                'usi' => 'Costa 2 punti stregoneria.',
                'testo' => 'Aggiungi o togli 1d4 al tiro di chiunque, dopo che ha tirato ma prima di sapere com\'è andata.',
            ],
        ],

        'Anima Divina' => [
            [
                'livello' => 1,
                'nome' => 'Magia divina',
                'costo' => 'passivo',
                'testo' => 'La lista da chierico è tua: puoi scegliere gli incantesimi anche da lì, e ne conosci uno legato all\'affinità che hai scelto.',
            ],
            [
                'livello' => 1,
                'nome' => 'Toccato dal divino',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Il tuo massimo di punti ferita sale di uno per livello, e quando fallisci un tiro salvezza puoi ripeterlo tenendo il secondo risultato.',
            ],
            [
                'livello' => 6,
                'nome' => 'Ali angeliche',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Ti spuntano ali di luce per un minuto: voli alla tua velocità e illumini tutto per 9 metri.',
            ],
        ],

        'Magia delle Ombre' => [
            [
                'livello' => 1,
                'nome' => 'Occhi della notte',
                'costo' => 'passivo',
                'usi' => 'Condividerla costa 1 punto stregoneria a testa.',
                'testo' => 'Vedi al buio fino a 36 metri, anche attraverso l\'oscurità magica, e puoi darla a chi ti sta vicino.',
            ],
            [
                'livello' => 1,
                'nome' => 'Fortezza di stregoneria',
                'costo' => 'passivo',
                'testo' => 'Quando scendi a 0 punti ferita e non muori sul colpo, puoi spendere 1 punto stregoneria per restare invece a 1.',
            ],
            [
                'livello' => 6,
                'nome' => 'Segugio di malasorte',
                'costo' => 'bonus',
                'usi' => 'Costa 3 punti stregoneria.',
                'testo' => 'Evochi un mastino d\'ombra che dà la caccia a una creatura per un minuto: finché le sta vicino hai vantaggio agli attacchi contro di lei. Lo vede solo chi ha la scurovisione.',
            ],
        ],

        'Stregoneria della Tempesta' => [
            [
                'livello' => 1,
                'nome' => 'Magia tempestosa',
                'costo' => 'bonus',
                'testo' => 'Ogni volta che lanci un incantesimo di 1º livello o più, voli fino a 3 metri senza provocare attacchi d\'opportunità.',
            ],
            [
                'livello' => 1,
                'nome' => 'Parlante del vento',
                'costo' => 'passivo',
                'testo' => 'Parli e capisci primordiale, e con lui aurano, aquan, ignan e terran.',
            ],
            [
                'livello' => 6,
                'nome' => 'Cuore della tempesta',
                'costo' => 'passivo',
                'testo' => 'Resistenza ai danni da fulmine e da tuono. E quando lanci un incantesimo di quel tipo, chi ti sta entro 3 metri prende danni pari a metà del tuo livello da stregone.',
            ],
        ],

        'Mente Aberrante' => [
            [
                'livello' => 1,
                'nome' => 'Sussurri telepatici',
                'costo' => 'bonus',
                'usi' => 'Gratis col bonus di competenza; oltre, costa 1 punto stregoneria.',
                'testo' => 'Parli nella mente di chiunque tu veda entro 9 metri, e se vuoi apri il collegamento a tutto il gruppo per un\'ora. Nessuno vi sente.',
            ],
            [
                'livello' => 1,
                'nome' => 'Incantesimi psionici',
                'costo' => 'passivo',
                'testo' => 'Impari incantesimi legati alla mente che non contano fra quelli noti, e col tempo puoi sostituirli con altri di divinazione o ammaliamento.',
            ],
            [
                'livello' => 6,
                'nome' => 'Difese psichiche',
                'costo' => 'passivo',
                'testo' => 'Resistenza ai danni psichici, e non puoi essere ammaliato né spaventato.',
            ],
        ],

        'Anima Meccanica' => [
            [
                'livello' => 1,
                'nome' => 'Ripristinare l\'equilibrio',
                'costo' => 'reazione',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Quando qualcuno entro 18 metri sta per tirare con vantaggio o con svantaggio, glielo togli: tira e basta.',
            ],
            [
                'livello' => 1,
                'nome' => 'Incantesimi meccanici',
                'costo' => 'passivo',
                'testo' => 'Impari incantesimi d\'ordine che non contano fra quelli noti, e conosci il trucchetto illusione minore.',
            ],
            [
                'livello' => 6,
                'nome' => 'Baluardo della legge',
                'costo' => 'azione',
                'usi' => 'Costa da 1 a 5 punti stregoneria.',
                'testo' => 'Circondi qualcuno entro 9 metri di un guscio d\'ordine: un dado di protezione per punto speso, che si tirano per togliere danni quando serve. Dura un minuto.',
            ],
        ],

        'Patrono: l\'Arcifata' => [
            [
                'livello' => 1,
                'nome' => 'Presenza fatata',
                'costo' => 'azione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Le creature entro 3 metri fanno un tiro salvezza su Saggezza: chi fallisce resta affascinato — o spaventato, scegli tu — fino alla fine del tuo turno successivo.',
            ],
            [
                'livello' => 6,
                'nome' => 'Fuga incantata',
                'costo' => 'reazione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Quando qualcuno ti attacca, sparisci prima che il colpo arrivi e ricompari fino a 18 metri più in là. L\'attacco manca.',
            ],
        ],

        'Patrono: il Grande Antico' => [
            [
                'livello' => 1,
                'nome' => 'Mente risvegliata',
                'costo' => 'passivo',
                'testo' => 'Parli nella mente di qualunque creatura entro 9 metri che capisca una lingua. Lei non può risponderti telepaticamente, ma ti sente.',
            ],
            [
                'livello' => 6,
                'nome' => 'Scudo entropico',
                'costo' => 'reazione',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Quando qualcuno tira per colpirti, ha svantaggio. E se manca, il tuo prossimo attacco contro di lui ha vantaggio.',
            ],
        ],

        'Patrono: la Lama Maledetta' => [
            [
                'livello' => 1,
                'nome' => 'Maledizione della lama',
                'costo' => 'bonus',
                'usi' => 'Una volta; torna col riposo breve o lungo.',
                'testo' => 'Maledici una creatura per un minuto: danni in più a ogni colpo pari al tuo bonus di competenza, critico con 19 e 20, e se muore recuperi punti ferita.',
            ],
            [
                'livello' => 1,
                'nome' => 'Guerriero occulto',
                'costo' => 'passivo',
                'testo' => 'Competenza nelle armature medie, negli scudi e nelle armi da guerra. E un\'arma legata a te con un rito usa il Carisma al posto di Forza e Destrezza: è il warlock che combatte davvero.',
            ],
            [
                'livello' => 6,
                'nome' => 'Spettro maledetto',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Quando uccidi un umanoide, la sua anima si alza come spettro e combatte per te fino al prossimo riposo lungo.',
            ],
        ],

        'Patrono: l\'Imperituro' => [
            [
                'livello' => 1,
                'nome' => 'Fra i morti',
                'costo' => 'passivo',
                'testo' => 'Conosci il trucchetto risparmiare i morenti, i non morti esitano a colpirti se non superano un tiro salvezza su Saggezza, e hai vantaggio ai tiri salvezza contro le malattie.',
            ],
            [
                'livello' => 6,
                'nome' => 'Sfidare la morte',
                'costo' => 'passivo',
                'usi' => 'Una volta; torna col riposo lungo.',
                'testo' => 'Quando riesci un tiro salvezza contro morte, o quando stabilizzi qualcuno, recuperi 1d8 punti ferita più il tuo modificatore di Costituzione.',
            ],
        ],

        'Patrono: il Genio' => [
            [
                'livello' => 1,
                'nome' => 'Vaso del genio',
                'costo' => 'azione',
                'usi' => 'Il rifugio, una volta per riposo lungo. L\'ira, una volta per turno.',
                'testo' => 'Hai un vaso legato al tuo patrono. Puoi entrarci dentro e restarci fino a dieci minuti — è un riposo breve al riparo da tutto — e una volta per turno aggiungi ai danni il tuo bonus di competenza, del tipo che il tuo genio preferisce.',
            ],
            [
                'livello' => 6,
                'nome' => 'Dono elementale',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Resistenza al tipo di danno del tuo genio, e puoi levitare per dieci minuti: voli a 9 metri.',
            ],
        ],

        'Patrono: le Profondità' => [
            [
                'livello' => 1,
                'nome' => 'Tentacolo delle profondità',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Fai comparire un tentacolo spettrale entro 18 metri. Lo sposti e colpisci con lui come azione bonus: danni da freddo, e chi prende il colpo rallenta.',
            ],
            [
                'livello' => 1,
                'nome' => 'Dono del mare',
                'costo' => 'passivo',
                'testo' => 'Nuoti alla tua velocità e respiri sott\'acqua.',
            ],
            [
                'livello' => 6,
                'nome' => 'Spira protettrice',
                'costo' => 'reazione',
                'testo' => 'Il tentacolo si avvolge intorno a qualcuno entro 3 metri da lui e ne assorbe una parte dei danni.',
            ],
        ],

        'Patrono: il Non Morto' => [
            [
                'livello' => 1,
                'nome' => 'Forma del terrore',
                'costo' => 'bonus',
                'usi' => 'Tante volte quanto il tuo bonus di competenza; tornano col riposo lungo.',
                'testo' => 'Per un minuto prendi l\'aspetto di quello che ti ha fatto il patto: punti ferita temporanei, immune alla paura, e una volta per turno chi colpisci con un critico — o chi fallisce un tiro salvezza su Saggezza — prende 1d10 danni psichici.',
            ],
            [
                'livello' => 6,
                'nome' => 'Toccato dalla tomba',
                'costo' => 'passivo',
                'testo' => 'Non ti serve più né mangiare né bere né respirare, e puoi trasformare in necrotici i danni di un incantesimo, una volta per turno.',
            ],
        ],
    ],
];
