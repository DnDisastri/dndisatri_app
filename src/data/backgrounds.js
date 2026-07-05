// === BACKGROUND (competenze = fatti; equipaggiamento/privilegi = sintesi originali) ===

export const BACKGROUNDS = {
  'Accolito': { skills: ['insight', 'religion'], gp: 15, equip: 'Simbolo sacro, libro di preghiere, 5 bastoncini d\'incenso, vesti, abiti comuni. 2 linguaggi.' },
  'Ciarlatano': { skills: ['deception', 'sleightOfHand'], gp: 15, equip: 'Abiti eleganti, kit da travestimento, attrezzatura per una truffa. Strumenti: kit da falsario e da trucco.' },
  'Criminale': { skills: ['deception', 'stealth'], gp: 15, equip: 'Piede di porco, abiti scuri col cappuccio. Strumenti: un gioco e arnesi da scasso.' },
  'Intrattenitore': { skills: ['acrobatics', 'performance'], gp: 15, equip: 'Uno strumento musicale, il favore di un ammiratore, un costume. Strumenti: kit da trucco.' },
  'Eroe Popolano': { skills: ['animalHandling', 'survival'], gp: 10, equip: 'Strumenti da artigiano, una pala, una pentola di ferro, abiti comuni. Competenza: veicoli terrestri.' },
  'Artigiano di Gilda': { skills: ['insight', 'persuasion'], gp: 15, equip: 'Strumenti da artigiano, lettera di presentazione della gilda, abiti da viaggio. 1 linguaggio.' },
  'Eremita': { skills: ['medicine', 'religion'], gp: 5, equip: 'Custodia con appunti, coperta, kit da erborista, abiti comuni. 1 linguaggio.' },
  'Nobile': { skills: ['history', 'persuasion'], gp: 25, equip: 'Abiti eleganti, anello con sigillo, pergamena del casato. Strumenti: un gioco. 1 linguaggio.' },
  'Forestiero': { skills: ['athletics', 'survival'], gp: 10, equip: 'Un bastone, una trappola, uno strumento musicale, abiti da viaggio. 1 linguaggio.' },
  'Sapiente': { skills: ['arcana', 'history'], gp: 10, equip: 'Boccetta d\'inchiostro, penna, coltellino, lettera di un collega defunto, abiti comuni. 2 linguaggi.' },
  'Marinaio': { skills: ['athletics', 'perception'], gp: 10, equip: 'Verga di ferro, corda di seta, portafortuna, abiti comuni. Strumenti: navigatore e veicoli acquatici.' },
  'Soldato': { skills: ['athletics', 'intimidation'], gp: 10, equip: 'Insegna di grado, trofeo di guerra, un gioco di dadi, abiti comuni. Strumenti: un gioco e veicoli terrestri.' },
  'Monello': { skills: ['sleightOfHand', 'stealth'], gp: 10, equip: 'Coltellino, mappa della città natale, un topo domestico, un souvenir dei genitori, abiti comuni. Strumenti: kit da trucco e arnesi da scasso.' }
};

export const BG_FEATURE = {
  'Accolito': 'Rifugio dei Fedeli: templi affini offrono ospitalità a te e ai tuoi compagni.',
  'Ciarlatano': 'Falsa Identità: possiedi una seconda identità documentata e credibile.',
  'Criminale': 'Contatto Criminale: hai una rete affidabile nel mondo del crimine.',
  'Intrattenitore': 'Richiesto ovunque: trovi sempre da esibirti in cambio di vitto e alloggio.',
  'Eroe Popolano': 'Ospitalità Rustica: la gente comune ti offre riparo e protezione.',
  'Artigiano di Gilda': 'Appartenenza alla Gilda: sostegno, alloggio e contatti dalla tua gilda.',
  'Eremita': 'Scoperta: dal tuo isolamento hai appreso un segreto unico e importante.',
  'Nobile': 'Posizione di Privilegio: sei accolto con rispetto dall\'alta società.',
  'Forestiero': 'Viandante: ti orienti sempre e puoi procurare cibo per il gruppo.',
  'Sapiente': 'Ricercatore: sai dove e da chi ottenere le informazioni che ti mancano.',
  'Marinaio': 'Passaggio in Nave: puoi ottenere un imbarco gratuito per te e i compagni.',
  'Soldato': 'Grado Militare: i soldati riconoscono la tua autorità di ex commilitone.',
  'Monello': 'Segreti della Città: ti muovi tra i vicoli al doppio della velocità normale.'
};

