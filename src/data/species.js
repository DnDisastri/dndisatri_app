// === SPECIE (bonus di caratteristica = fatti; tratti = sintesi originali) ===

export const SPECIES = {
  'Umano': { asi: { all: 1 }, speed: 9, traits: 'Versatile: +1 a tutte le caratteristiche. Velocità 9 m.' },
  'Umano (Variante)': { asi: {}, speed: 9, variant: true, traits: '+1 a due caratteristiche a scelta, una competenza in un\'abilità e un talento (al livello 1). Velocità 9 m.' },
  'Nano': { asi: { con: 2 }, speed: 7.5, traits: '+2 Costituzione. Scurovisione 18 m. Resistenza al veleno. Competenza con asce e martelli. Velocità 7,5 m.' },
  'Elfo': { asi: { dex: 2 }, speed: 9, traits: '+2 Destrezza. Scurovisione 18 m. Vantaggio contro ammaliamento e immunità al sonno magico. Percezione competente.' },
  'Halfling': { asi: { dex: 2 }, speed: 7.5, traits: '+2 Destrezza. Fortunato: ritira gli 1 sui d20 di attacco/prova/TS. Vantaggio contro lo spavento. Velocità 7,5 m.' },
  'Dragonide': { asi: { str: 2, cha: 1 }, speed: 9, traits: '+2 Forza, +1 Carisma. Arma del soffio e resistenza a un tipo di danno in base al lignaggio.' },
  'Gnomo': { asi: { int: 2 }, speed: 7.5, traits: '+2 Intelligenza. Scurovisione 18 m. Vantaggio ai TS di INT/SAG/CAR contro la magia. Velocità 7,5 m.' },
  'Mezzelfo': { asi: { cha: 2 }, speed: 9, traits: '+2 Carisma e +1 a due caratteristiche a scelta (da assegnare a mano). Scurovisione. Due competenze in abilità.' },
  'Mezzorco': { asi: { str: 2, con: 1 }, speed: 9, traits: '+2 Forza, +1 Costituzione. Scurovisione. Resistenza implacabile (resti a 1 PF una volta al giorno). Attacchi brutali.' },
  'Tiefling': { asi: { cha: 2, int: 1 }, speed: 9, traits: '+2 Carisma, +1 Intelligenza. Scurovisione. Resistenza al fuoco. Conosce il trucchetto Taumaturgia.' }
};

