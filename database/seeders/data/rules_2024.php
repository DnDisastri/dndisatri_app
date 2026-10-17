<?php

/*
 * Specie, sottospecie e talenti delle regole 2024.
 *
 * ATTRIBUZIONE (richiesta dalla licenza, non facoltativa)
 * Quest'opera include materiale tratto dal System Reference Document 5.2.1
 * ("SRD 5.2.1") di Wizards of the Coast LLC, disponibile all'indirizzo
 * https://www.dndbeyond.com/srd. Il SRD 5.2.1 è concesso in licenza ai sensi
 * della licenza di attribuzione 4.0 Internazionale di Creative Commons,
 * disponibile all'indirizzo https://creativecommons.org/licenses/by/4.0/legalcode.
 *
 * I testi sono riassunti nostri. Nel 2024 le specie non danno bonus alle
 * caratteristiche: arrivano dal background. Dopo la prima migrazione si
 * cambia tutto dal pannello.
 */

return [

    'species' => [
        [
            'name' => 'Umano',
            'speed' => 9,
            'description' => 'Ovunque e capaci di tutto: ogni giorno è un\'occasione per migliorarsi.',
            'traits' => "Intraprendente: dopo ogni riposo lungo hai l'ispirazione eroica.\nAbile: competenza in un'abilità a scelta.\nVersatile: un talento di origine a scelta.",
            'skill_choices' => 1,
            'grants_feat' => true,
        ],
        [
            'name' => 'Dragonide',
            'speed' => 9,
            'description' => 'Discendenti dei draghi, con il loro soffio e la loro fierezza.',
            'traits' => "Scurovisione 18 m.\nArma a soffio: al posto di un attacco, cono di 4,5 m o linea di 9 m; tiro salvezza su Destrezza, 1d10 danni del tipo della discendenza (2d10 dal 5º, 3d10 dall'11º, 4d10 dal 17º). Tante volte quanto il bonus di competenza, tornano col riposo lungo.\nResistenza al tipo di danno della discendenza.\nVolo draconico (dal 5º livello): ali spettrali per 10 minuti, una volta per riposo lungo.",
        ],
        [
            'name' => 'Nano',
            'speed' => 9,
            'description' => 'Forgiati nella pietra: tenaci, resistenti, a casa sotto terra.',
            'traits' => "Scurovisione 36 m.\nResilienza nanica: resistenza ai danni da veleno, vantaggio ai tiri salvezza contro l'avvelenamento.\nRobustezza nanica: +1 punto ferita massimo per livello.\nConoscenza della pietra: con un'azione bonus percepisci il terreno entro 18 m per 10 minuti. Tante volte quanto il bonus di competenza, tornano col riposo lungo.",
        ],
        [
            'name' => 'Elfo',
            'speed' => 9,
            'description' => 'Longevi e legati alla magia fatata; non dormono, meditano.',
            'traits' => "Scurovisione 18 m.\nAscendenza fatata: vantaggio ai tiri salvezza per non essere affascinato.\nSensi acuti: competenza in Intuizione, Percezione o Sopravvivenza.\nTrance: un riposo lungo ti basta in 4 ore di meditazione.\nIl lignaggio aggiunge magia e altro.",
            'skill_choices' => 1,
        ],
        [
            'name' => 'Gnomo',
            'speed' => 9,
            'description' => 'Piccoli, curiosi e inventivi, con una mente difficile da ingannare.',
            'traits' => "Taglia piccola. Scurovisione 18 m.\nAstuzia gnomesca: vantaggio ai tiri salvezza su Intelligenza, Saggezza e Carisma.\nIl lignaggio aggiunge la sua magia.",
        ],
        [
            'name' => 'Goliath',
            'speed' => 10.5,
            'description' => 'Discendenti dei giganti, grandi e forti come le montagne da cui vengono.',
            'traits' => "Ascendenza gigante: un dono del gigante scelto, tante volte quanto il bonus di competenza, tornano col riposo lungo.\nForma enorme (dal 5º livello): diventi Grande per 10 minuti, con vantaggio alle prove di Forza; una volta per riposo lungo.\nCorporatura possente: vantaggio per liberarti dall'essere afferrato, e porti carichi come una taglia più grande.",
        ],
        [
            'name' => 'Halfling',
            'speed' => 9,
            'description' => 'Piccoli, svelti e fortunati: il pericolo sembra scivolargli addosso.',
            'traits' => "Taglia piccola.\nCoraggioso: vantaggio ai tiri salvezza per non essere spaventato.\nAgilità halfling: attraversi lo spazio di creature più grandi di te.\nFortunato: quando fai 1 su un d20 di attacco, prova o tiro salvezza, ritiri il dado.\nFurtività naturale: puoi nasconderti anche dietro una creatura più grande.",
        ],
        [
            'name' => 'Orco',
            'speed' => 9,
            'description' => 'Instancabili e determinati, con una forza che non si arrende.',
            'traits' => "Scurovisione 36 m.\nScatto di adrenalina: Scatto come azione bonus, e guadagni punti ferita temporanei pari al bonus di competenza. Tante volte quanto il bonus di competenza, tornano col riposo breve o lungo.\nTenacia implacabile: quando scendi a 0 punti ferita resti a 1, una volta per riposo lungo.",
        ],
        [
            'name' => 'Tiefling',
            'speed' => 9,
            'description' => 'Segnati da un\'eredità dei piani inferiori, nel sangue e nella magia.',
            'traits' => "Scurovisione 18 m.\nPresenza ultraterrena: conosci il trucchetto Taumaturgia.\nL'eredità diabolica aggiunge resistenza e magia.",
        ],
    ],

    // Lignaggi, discendenze, ascendenze ed eredità: nel 2024 non danno bonus alle caratteristiche.
    'subraces' => [
        'Dragonide' => [
            ['name' => 'Blu', 'traits' => 'Danni da fulmine.'],
            ['name' => 'Bianco', 'traits' => 'Danni da freddo.'],
            ['name' => 'Bronzo', 'traits' => 'Danni da fulmine.'],
            ['name' => 'Nero', 'traits' => 'Danni da acido.'],
            ['name' => 'Oro', 'traits' => 'Danni da fuoco.'],
            ['name' => 'Ottone', 'traits' => 'Danni da fuoco.'],
            ['name' => 'Argento', 'traits' => 'Danni da freddo.'],
            ['name' => 'Rame', 'traits' => 'Danni da acido.'],
            ['name' => 'Rosso', 'traits' => 'Danni da fuoco.'],
            ['name' => 'Verde', 'traits' => 'Danni da veleno.'],
        ],
        'Elfo' => [
            ['name' => 'Drow', 'description' => 'Elfi del Sottosuolo.', 'traits' => 'Scurovisione 36 m. Trucchetto Luci danzanti; Luminescenza faerica dal 3º, Oscurità dal 5º.'],
            ['name' => 'Elfo Alto', 'description' => 'Elfi della magia arcana.', 'traits' => 'Trucchetto Prestidigitazione (lo puoi cambiare dopo un riposo lungo); Individuazione del magico dal 3º, Passo velato dal 5º.'],
            ['name' => 'Elfo Silvano', 'description' => 'Elfi dei boschi, rapidi e silenziosi.', 'speed' => 10.5, 'traits' => 'Velocità 10,5 m. Trucchetto Arte druidica; Passo veloce dal 3º, Passare senza tracce dal 5º.'],
        ],
        'Gnomo' => [
            ['name' => 'Gnomo delle Foreste', 'traits' => 'Trucchetto Illusione minore; puoi lanciare Parlare con gli animali tante volte quanto il bonus di competenza.'],
            ['name' => 'Gnomo delle Rocce', 'traits' => 'Trucchetti Riparare e Prestidigitazione; costruisci piccoli congegni a orologeria.'],
        ],
        'Goliath' => [
            ['name' => 'Gigante delle Colline', 'traits' => 'Quando colpisci una creatura Grande o più piccola, puoi farla cadere prona.'],
            ['name' => 'Gigante delle Nuvole', 'traits' => 'Con un\'azione bonus ti teletrasporti fino a 9 m.'],
            ['name' => 'Gigante delle Pietre', 'traits' => 'Quando subisci danni, con la reazione li riduci di 1d12 più la Costituzione.'],
            ['name' => 'Gigante delle Tempeste', 'traits' => 'Quando qualcuno entro 18 m ti colpisce, con la reazione gli infliggi 1d8 danni da tuono.'],
            ['name' => 'Gigante del Fuoco', 'traits' => 'Quando colpisci, aggiungi 1d10 danni da fuoco.'],
            ['name' => 'Gigante del Gelo', 'traits' => 'Quando colpisci, aggiungi 1d6 danni da freddo e il bersaglio rallenta di 3 m.'],
        ],
        'Tiefling' => [
            ['name' => 'Abissale', 'traits' => 'Resistenza ai danni da veleno. Trucchetto Spruzzo velenoso; Raggio di infermità dal 3º, Blocca persone dal 5º.'],
            ['name' => 'Ctonio', 'traits' => 'Resistenza ai danni necrotici. Trucchetto Tocco gelido; Vita falsata dal 3º, Raggio di indebolimento dal 5º.'],
            ['name' => 'Infernale', 'traits' => 'Resistenza ai danni da fuoco. Trucchetto Dardo di fuoco; Rimprovero infernale dal 3º, Oscurità dal 5º.'],
        ],
    ],

    'feats' => [
        // Di origine: dal background, o dall'Umano.
        ['name' => 'Abile', 'category' => 'origin', 'description' => 'Competenza in tre abilità o strumenti a scelta. Si può prendere più volte.'],
        ['name' => 'Allerta', 'category' => 'origin', 'description' => "Aggiungi il bonus di competenza all'iniziativa, e subito dopo averla tirata puoi scambiarla con quella di un alleato consenziente."],
        ['name' => 'Attaccante selvaggio', 'category' => 'origin', 'description' => "Una volta per turno, quando colpisci con un'arma, tiri due volte i dadi dei danni e tieni il risultato migliore."],
        ['name' => 'Iniziato alla magia (Chierico)', 'category' => 'origin', 'description' => "Due trucchetti e un incantesimo di 1º dalla lista del Chierico; l'incantesimo lo lanci una volta gratis per riposo lungo, poi con gli slot. Caratteristica: Intelligenza, Saggezza o Carisma."],
        ['name' => 'Iniziato alla magia (Druido)', 'category' => 'origin', 'description' => "Due trucchetti e un incantesimo di 1º dalla lista del Druido; l'incantesimo lo lanci una volta gratis per riposo lungo, poi con gli slot. Caratteristica: Intelligenza, Saggezza o Carisma."],
        ['name' => 'Iniziato alla magia (Mago)', 'category' => 'origin', 'description' => "Due trucchetti e un incantesimo di 1º dalla lista del Mago; l'incantesimo lo lanci una volta gratis per riposo lungo, poi con gli slot. Caratteristica: Intelligenza, Saggezza o Carisma."],

        // Generali: dal 4º livello.
        ['name' => 'Aumento dei punteggi di caratteristica', 'category' => 'general', 'prerequisite' => '4º livello', 'description' => '+2 a una caratteristica o +1 a due, fino a 20. Si può prendere più volte.'],
        ['name' => 'Lottatore', 'category' => 'general', 'prerequisite' => '4º livello, Forza o Destrezza 13', 'description' => '+1 a Forza o Destrezza. Quando colpisci con un colpo senz\'armi puoi anche afferrare, hai vantaggio contro chi stai afferrando, e muoverti trascinandolo non ti costa movimento in più.'],

        // Stili di combattimento: da chi ha il privilegio.
        ['name' => 'Combattere con armi grandi', 'category' => 'fighting_style', 'prerequisite' => 'Privilegio Stile di combattimento', 'description' => 'Con un\'arma da mischia a due mani o versatile impugnata a due mani, gli 1 e i 2 sui dadi dei danni contano come 3.'],
        ['name' => 'Combattere con due armi', 'category' => 'fighting_style', 'prerequisite' => 'Privilegio Stile di combattimento', 'description' => "Quando attacchi con l'arma nell'altra mano, aggiungi ai danni il modificatore della caratteristica."],
        ['name' => 'Difesa', 'category' => 'fighting_style', 'prerequisite' => 'Privilegio Stile di combattimento', 'description' => "+1 alla CA mentre indossi un'armatura leggera, media o pesante."],
        ['name' => 'Tiro', 'category' => 'fighting_style', 'prerequisite' => 'Privilegio Stile di combattimento', 'description' => '+2 ai tiri per colpire con le armi a distanza.'],

        // Doni epici: dal 19º livello.
        ['name' => "Dono dell'abilità in combattimento", 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => '+1 a una caratteristica (fino a 30). Una volta per turno, quando manchi un attacco, puoi colpire comunque.'],
        ['name' => 'Dono del viaggio dimensionale', 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => "+1 a una caratteristica (fino a 30). Dopo l'azione di attacco o una magia, ti teletrasporti fino a 9 m."],
        ['name' => 'Dono del fato', 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => '+1 a una caratteristica (fino a 30). Quando qualcuno entro 18 m fa una prova o un tiro salvezza, gli aggiungi o togli 2d4; una volta per riposo.'],
        ['name' => "Dono dell'offesa irresistibile", 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => '+1 a Forza o Destrezza (fino a 30). I tuoi danni contundenti, perforanti e taglienti ignorano la resistenza, e con un 20 naturale aggiungi danni pari al punteggio aumentato.'],
        ['name' => 'Dono del richiamo degli incantesimi', 'category' => 'epic_boon', 'prerequisite' => '19º livello, saper lanciare incantesimi', 'description' => '+1 a Intelligenza, Saggezza o Carisma (fino a 30). Una volta per riposo lungo lanci un incantesimo fino al 4º livello senza spendere lo slot.'],
        ['name' => 'Dono dello spirito notturno', 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => "+1 a una caratteristica (fino a 30). Nell'oscurità o nella penombra diventi invisibile con un'azione bonus, e lì resisti a quasi tutti i danni."],
        ['name' => 'Dono della vista vera', 'category' => 'epic_boon', 'prerequisite' => '19º livello', 'description' => '+1 a una caratteristica (fino a 30). Vedi la vera natura delle cose entro 18 m: invisibili, illusioni, trasformazioni.'],
    ],
];
