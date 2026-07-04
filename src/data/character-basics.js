// === DATI BASE PERSONAGGIO (mappe pure) ===
// Estratto da app.js durante il refactor a moduli ES.

export const STAT_LABELS = { str: 'FOR', dex: 'DES', con: 'COS', int: 'INT', wis: 'SAG', cha: 'CAR' };

// Point buy: costo cumulativo per punteggio e ordine delle caratteristiche.
export const PB_COST = { 8: 0, 9: 1, 10: 2, 11: 3, 12: 4, 13: 5, 14: 7, 15: 9 };
export const PB_ABILITIES = [['str', 'Forza (FOR)'], ['dex', 'Destrezza (DES)'], ['con', 'Costituzione (COS)'], ['int', 'Intelligenza (INT)'], ['wis', 'Saggezza (SAG)'], ['cha', 'Carisma (CAR)']];

// Abilità → caratteristica associata.
export const SKILLS_MAP = {
  athletics: 'str',
  acrobatics: 'dex',
  sleightOfHand: 'dex',
  stealth: 'dex',
  arcana: 'int',
  history: 'int',
  investigation: 'int',
  nature: 'int',
  religion: 'int',
  animalHandling: 'wis',
  insight: 'wis',
  medicine: 'wis',
  perception: 'wis',
  survival: 'wis',
  deception: 'cha',
  intimidation: 'cha',
  performance: 'cha',
  persuasion: 'cha'
};

// Abilità → nome italiano.
export const SKILLS_ITALIAN = {
  athletics: 'Atletica',
  acrobatics: 'Acrobazia',
  sleightOfHand: 'Rapidità di Mano',
  stealth: 'Furtività',
  arcana: 'Arcano',
  history: 'Storia',
  investigation: 'Indagare',
  nature: 'Natura',
  religion: 'Religione',
  animalHandling: 'Addestrare Animali',
  insight: 'Intuizione',
  medicine: 'Medicina',
  perception: 'Percezione',
  survival: 'Sopravvivenza',
  deception: 'Inganno',
  intimidation: 'Intimidire',
  performance: 'Intrattenere',
  persuasion: 'Persuasione'
};

// Caratteristica → nome italiano completo (per i tiri salvezza).
export const SAVE_NAMES = {
  str: 'Forza',
  dex: 'Destrezza',
  con: 'Costituzione',
  int: 'Intelligenza',
  wis: 'Saggezza',
  cha: 'Carisma'
};
