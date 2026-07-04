// === DATI INCANTESIMI ===
// Estratto da app.js durante il refactor a moduli ES. Solo dati puri (fatti di gioco +
// sintesi originali) e l'helper di normalizzazione dei nomi. Nessuna dipendenza dal DOM.

// Liste incantesimi per classe (fatti di gioco), limitate alla libreria interna
export const CLASS_SPELL_LIST = {
  'Mago': ['Dardo di Fuoco', 'Mano Magica', 'Luce', 'Prestidigitazione', 'Illusione Minore', 'Raggio di Gelo', 'Tocco Gelido', 'Dardo Incantato', 'Scudo', 'Armatura Magica', 'Mani Brucianti', 'Charme su Persone', 'Sonno', 'Individuazione del Magico', 'Nube di Nebbia', 'Raggio Rovente', 'Passo Velato', 'Invisibilità', 'Immagine Speculare', 'Blocca Persone', 'Frantumare', 'Palla di Fuoco', 'Fulmine', 'Controincantesimo', 'Dissolvi Magie', 'Volare', 'Porta Dimensionale', 'Polimorfia', 'Cono di Freddo', 'Muro di Forza'],
  'Chierico': ['Fiamma Sacra', 'Luce', 'Taumaturgia', 'Guida', 'Cura Ferite', 'Parola Guaritrice', 'Benedizione', 'Comando', 'Individuazione del Magico', 'Ristorare Inferiore', 'Rivitalizzare', 'Dissolvi Magie', 'Rianimare Morti'],
  'Stregone': ['Dardo di Fuoco', 'Mano Magica', 'Luce', 'Prestidigitazione', 'Raggio di Gelo', 'Dardo Incantato', 'Scudo', 'Armatura Magica', 'Mani Brucianti', 'Charme su Persone', 'Sonno', 'Nube di Nebbia', 'Passo Velato', 'Invisibilità', 'Immagine Speculare', 'Frantumare', 'Palla di Fuoco', 'Fulmine', 'Controincantesimo', 'Volare', 'Polimorfia', 'Cono di Freddo'],
  'Bardo': ['Mano Magica', 'Luce', 'Prestidigitazione', 'Illusione Minore', 'Cura Ferite', 'Parola Guaritrice', 'Charme su Persone', 'Sonno', 'Comando', 'Invisibilità', 'Immagine Speculare', 'Blocca Persone', 'Dissolvi Magie', 'Polimorfia'],
  'Druido': ['Guida', 'Luce', 'Cura Ferite', 'Individuazione del Magico', 'Nube di Nebbia', 'Frantumare', 'Volare', 'Dissolvi Magie', 'Cono di Freddo'],
  'Warlock': ['Tocco Gelido', 'Mano Magica', 'Illusione Minore', 'Charme su Persone', 'Passo Velato', 'Invisibilità', 'Blocca Persone', 'Controincantesimo', 'Dissolvi Magie', 'Volare', 'Polimorfia'],
  'Paladino': ['Cura Ferite', 'Benedizione', 'Comando', 'Individuazione del Magico'],
  'Ranger': ['Cura Ferite', 'Nube di Nebbia', 'Individuazione del Magico']
};

// Caratteristica da incantatore per classe (fatto di gioco). Terzo-caster (sottoclassi) usano Intelligenza.
export const CLASS_SPELL_ABILITY = {
  'Mago': 'int', 'Chierico': 'wis', 'Druido': 'wis', 'Bardo': 'cha',
  'Stregone': 'cha', 'Warlock': 'cha', 'Paladino': 'cha', 'Ranger': 'wis'
};

// Trucchetti e incantesimi noti a LIVELLO 1. Solo classi che a liv.1 hanno già incantesimi.
// 'prepared' = mod(caratteristica) + livello, calcolato a runtime. Paladino/Ranger (half caster)
// e i terzo-caster non compaiono: iniziano a lanciare più avanti.
export const SPELLS_KNOWN_L1 = {
  'Bardo':    { cantrips: 2, spells: 4 },
  'Chierico': { cantrips: 3, spells: 'prepared' },
  'Druido':   { cantrips: 2, spells: 'prepared' },
  'Mago':     { cantrips: 3, spells: 6 },
  'Stregone': { cantrips: 4, spells: 2 },
  'Warlock':  { cantrips: 2, spells: 2 }
};

// Tabelle slot: [slot liv.1, liv.2, ...] indicizzate per livello del personaggio
export const FULL_CASTER_SLOTS = {
  1: [2], 2: [3], 3: [4, 2], 4: [4, 3], 5: [4, 3, 2], 6: [4, 3, 3], 7: [4, 3, 3, 1], 8: [4, 3, 3, 2],
  9: [4, 3, 3, 3, 1], 10: [4, 3, 3, 3, 2], 11: [4, 3, 3, 3, 2, 1], 12: [4, 3, 3, 3, 2, 1],
  13: [4, 3, 3, 3, 2, 1, 1], 14: [4, 3, 3, 3, 2, 1, 1], 15: [4, 3, 3, 3, 2, 1, 1, 1], 16: [4, 3, 3, 3, 2, 1, 1, 1],
  17: [4, 3, 3, 3, 2, 1, 1, 1, 1], 18: [4, 3, 3, 3, 3, 1, 1, 1, 1], 19: [4, 3, 3, 3, 3, 2, 1, 1, 1], 20: [4, 3, 3, 3, 3, 2, 2, 1, 1]
};
export const HALF_CASTER_SLOTS = {
  2: [2], 3: [3], 4: [3], 5: [4, 2], 6: [4, 2], 7: [4, 3], 8: [4, 3], 9: [4, 3, 2], 10: [4, 3, 2],
  11: [4, 3, 3], 12: [4, 3, 3], 13: [4, 3, 3, 1], 14: [4, 3, 3, 1], 15: [4, 3, 3, 2], 16: [4, 3, 3, 2],
  17: [4, 3, 3, 3, 1], 18: [4, 3, 3, 3, 1], 19: [4, 3, 3, 3, 2], 20: [4, 3, 3, 3, 2]
};
export const THIRD_CASTER_SLOTS = {
  3: [2], 4: [3], 5: [3], 6: [3], 7: [4, 2], 8: [4, 2], 9: [4, 2], 10: [4, 3], 11: [4, 3], 12: [4, 3],
  13: [4, 3, 2], 14: [4, 3, 2], 15: [4, 3, 2], 16: [4, 3, 3], 17: [4, 3, 3], 18: [4, 3, 3], 19: [4, 3, 3, 1], 20: [4, 3, 3, 1]
};
// Warlock Pact Magic: [numero slot, livello slot] per livello del warlock
export const WARLOCK_SLOTS = {
  1: [1, 1], 2: [2, 1], 3: [2, 2], 4: [2, 2], 5: [2, 3], 6: [2, 3], 7: [2, 4], 8: [2, 4], 9: [2, 5], 10: [2, 5],
  11: [3, 5], 12: [3, 5], 13: [3, 5], 14: [3, 5], 15: [3, 5], 16: [3, 5], 17: [4, 5], 18: [4, 5], 19: [4, 5], 20: [4, 5]
};

// === LIBRERIA INCANTESIMI (sintesi originali, non testo ufficiale) ===
export function normalizeSpellName(name) {
  return (name || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]/g, '');
}

export const SPELL_LIST = [
  ['Dardo di Fuoco', 'Trucchetto di Evocazione. Azione, gittata 36 m. Tiro per colpire a distanza; 1d10 danni da fuoco (2d10 al 5°, 3d10 all\'11°, 4d10 al 17°). Può incendiare oggetti.'],
  ['Fiamma Sacra', 'Trucchetto di Evocazione. Azione, 18 m. Il bersaglio effettua TS su Destrezza o subisce 1d8 danni radiosi (sale ai livelli superiori). Ignora la copertura.'],
  ['Spruzzo Acido', 'Trucchetto di Evocazione. Azione, 18 m. Fino a due creature vicine tirano TS su Destrezza o subiscono 1d6 danni acidi.'],
  ['Veleno Spettrale', 'Trucchetto di Necromanzia. Azione, 3 m. TS su Costituzione o 1d12 danni da veleno.'],
  ['Mano Magica', 'Trucchetto di Evocazione. Azione, 9 m. Crea una mano spettrale che manipola oggetti leggeri (fino a 5 kg).'],
  ['Luce', 'Trucchetto di Invocazione. Azione, tocco. Un oggetto emette luce intensa per 6 m (durata 1 ora).'],
  ['Prestidigitazione', 'Trucchetto di Trasmutazione. Piccolo effetto innocuo: accendere/spegnere, pulire, creare un\'illusione sensoriale minore.'],
  ['Illusione Minore', 'Trucchetto di Illusione. Crea un suono o un\'immagine statica per 1 minuto.'],
  ['Guida', 'Trucchetto di Divinazione. Azione, tocco (concentrazione). Il bersaglio aggiunge 1d4 a una prova di caratteristica entro 1 minuto.'],
  ['Raggio di Gelo', 'Trucchetto di Invocazione. Azione, 18 m. Attacco a distanza; 1d8 danni da freddo e -3 m alla velocità.'],
  ['Tocco Gelido', 'Trucchetto di Necromanzia. Azione, 36 m. Attacco a distanza; 1d8 danni necrotici, il bersaglio non recupera PF fino al tuo prossimo turno.'],
  ['Taumaturgia', 'Trucchetto di Trasmutazione. Piccola manifestazione di potere divino: voce amplificata, tremori, porte che sbattono.'],
  ['Dardo Incantato', 'Evocazione, liv. 1. Azione, 36 m. Tre dardi di forza, 1d4+1 ciascuno; colpiscono automaticamente. +1 dardo per slot superiore.'],
  ['Cura Ferite', 'Invocazione, liv. 1. Azione, tocco. Cura 1d8 + modificatore da incantatore. +1d8 per slot superiore.'],
  ['Parola Guaritrice', 'Invocazione, liv. 1. Azione bonus, 18 m. Cura 1d4 + modificatore. +1d4 per slot superiore.'],
  ['Scudo', 'Abiurazione, liv. 1. Reazione. +5 alla CA fino al tuo prossimo turno; annulla Dardo Incantato.'],
  ['Armatura Magica', 'Abiurazione, liv. 1. Tocco. La CA base del bersaglio diventa 13 + mod. Destrezza per 8 ore.'],
  ['Mani Brucianti', 'Evocazione, liv. 1. Azione, cono di 4,5 m. TS su Destrezza; 3d6 danni da fuoco (metà se supera).'],
  ['Onda Tonante', 'Invocazione, liv. 1. Azione, cubo di 4,5 m. TS su Costituzione; 2d8 danni da tuono e spinta di 3 m.'],
  ['Charme su Persone', 'Ammaliamento, liv. 1. Azione, 9 m. TS su Saggezza o il bersaglio ti considera amichevole per 1 ora.'],
  ['Sonno', 'Ammaliamento, liv. 1. Azione, 27 m. Fa addormentare creature per un totale di 5d8 PF, partendo dalle più deboli.'],
  ['Benedizione', 'Ammaliamento, liv. 1. Fino a 3 creature aggiungono 1d4 a tiri per colpire e TS (concentrazione, 1 min).'],
  ['Comando', 'Ammaliamento, liv. 1. Azione, 18 m. TS su Saggezza o il bersaglio obbedisce a un ordine di una parola.'],
  ['Individuazione del Magico', 'Divinazione, liv. 1. Percepisci presenza e scuola di magia entro 9 m (concentrazione).'],
  ['Nube di Nebbia', 'Evocazione, liv. 1. Crea una sfera di nebbia di 6 m che oscura la vista (concentrazione).'],
  ['Raggio Rovente', 'Evocazione, liv. 2. Azione, 36 m. Tre raggi, attacco a distanza ciascuno; 2d6 danni da fuoco per raggio.'],
  ['Passo Velato', 'Evocazione, liv. 2. Azione bonus. Teletrasporto fino a 9 m in uno spazio visibile.'],
  ['Invisibilità', 'Illusione, liv. 2. Tocco; il bersaglio diventa invisibile fino a 1 ora o finché attacca/lancia (concentrazione).'],
  ['Immagine Speculare', 'Illusione, liv. 2. Crea 3 duplicati che possono far fallire gli attacchi diretti a te.'],
  ['Blocca Persone', 'Ammaliamento, liv. 2. 18 m. TS su Saggezza o il bersaglio è paralizzato (concentrazione).'],
  ['Ristorare Inferiore', 'Abiurazione, liv. 2. Tocco. Rimuove una malattia o una condizione (cieco, sordo, paralizzato, avvelenato).'],
  ['Frantumare', 'Invocazione, liv. 2. 18 m, sfera di 3 m. TS su Costituzione; 3d8 danni da tuono.'],
  ['Palla di Fuoco', 'Evocazione, liv. 3. Azione, 45 m, sfera di 6 m. TS su Destrezza; 8d6 danni da fuoco (metà se supera). +1d6 per slot superiore.'],
  ['Fulmine', 'Invocazione, liv. 3. Linea di 30 m. TS su Destrezza; 8d6 danni da fulmine.'],
  ['Controincantesimo', 'Abiurazione, liv. 3. Reazione, 18 m. Interrompe un incantesimo di livello 3 o inferiore (prova per quelli superiori).'],
  ['Dissolvi Magie', 'Abiurazione, liv. 3. Termina effetti magici di livello 3 o inferiore (prova per i superiori).'],
  ['Rivitalizzare', 'Necromanzia, liv. 3. Tocco. Riporta in vita una creatura morta da non più di 1 minuto, con 1 PF.'],
  ['Volare', 'Trasmutazione, liv. 3. Tocco. Il bersaglio ottiene velocità di volo 18 m per 10 minuti (concentrazione).'],
  ['Porta Dimensionale', 'Evocazione, liv. 4. Azione. Teletrasporto fino a 150 m, anche con un\'altra creatura consenziente.'],
  ['Polimorfia', 'Trasmutazione, liv. 4. 18 m. TS su Saggezza o il bersaglio si trasforma in una bestia (concentrazione, 1 ora).'],
  ['Cono di Freddo', 'Invocazione, liv. 5. Cono di 18 m. TS su Costituzione; 8d8 danni da freddo.'],
  ['Rianimare Morti', 'Necromanzia, liv. 5. Riporta in vita una creatura morta da non più di 10 giorni.'],
  ['Muro di Forza', 'Invocazione, liv. 5. Crea una barriera invisibile e quasi indistruttibile (concentrazione, 10 min).']
];

export const SPELL_LIB = {};
SPELL_LIST.forEach(([n, d]) => { SPELL_LIB[normalizeSpellName(n)] = d; });
