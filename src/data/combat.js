// === ARMI E ARMATURE (fatti di gioco) ===

export const ARMOR = {
  'Armatura Imbottita': { type: 'light', base: 11 },
  'Armatura di Cuoio': { type: 'light', base: 11 },
  'Cuoio Borchiato': { type: 'light', base: 12 },
  'Corazza di Scaglie': { type: 'medium', base: 14 },
  'Corpetto (Corazza a Bande)': { type: 'medium', base: 14 },
  'Mezza Armatura': { type: 'medium', base: 15 },
  'Cotta di Maglia': { type: 'heavy', base: 16 },
  'Armatura a Piastre': { type: 'heavy', base: 18 }
};

export const SHIELDS = { 'Scudo': 2, 'Scudo +1': 3 };

export const WEAPONS_DATA = {
  'Pugnale': { damage: '1d4', stat: 'dex' },
  'Spada Corta': { damage: '1d6', stat: 'dex' },
  'Spada Lunga': { damage: '1d8', stat: 'str' },
  'Spadone': { damage: '2d6', stat: 'str' },
  'Ascia da Battaglia': { damage: '1d8', stat: 'str' },
  'Ascia Bipenne': { damage: '1d12', stat: 'str' },
  'Mazza': { damage: '1d6', stat: 'str' },
  'Martello da Guerra': { damage: '1d8', stat: 'str' },
  'Maglio': { damage: '2d6', stat: 'str' },
  'Lancia': { damage: '1d6', stat: 'str' },
  'Alabarda': { damage: '1d10', stat: 'str' },
  'Arco Corto': { damage: '1d6', stat: 'dex' },
  'Arco Lungo': { damage: '1d8', stat: 'dex' },
  'Balestra Leggera': { damage: '1d8', stat: 'dex' },
  'Balestra Pesante': { damage: '1d10', stat: 'dex' },
  'Arma +1': { damage: '1d8', stat: 'str', magic: 1 }
};

