// === EQUIPAGGIAMENTO INIZIALE STRUTTURATO (liste di oggetti = fatti) ===

export const CLASS_START = {
  'Barbaro': [{ name: 'Ascia Bipenne', qty: 1, category: 'Armi' }, { name: 'Ascia da Battaglia', qty: 2, category: 'Armi' }],
  'Bardo': [{ name: 'Armatura di Cuoio', qty: 1, category: 'Armature' }, { name: 'Spada Corta', qty: 1, category: 'Armi' }, { name: 'Pugnale', qty: 1, category: 'Armi' }, { name: 'Strumento Musicale', qty: 1, category: 'Kit e Strumenti' }],
  'Chierico': [{ name: 'Corazza di Scaglie', qty: 1, category: 'Armature' }, { name: 'Mazza', qty: 1, category: 'Armi' }, { name: 'Scudo', qty: 1, category: 'Armature' }, { name: 'Balestra Leggera', qty: 1, category: 'Armi' }],
  'Druido': [{ name: 'Armatura di Cuoio', qty: 1, category: 'Armature' }, { name: 'Scudo', qty: 1, category: 'Armature' }, { name: 'Lancia', qty: 1, category: 'Armi' }, { name: 'Kit da Erborista', qty: 1, category: 'Kit e Strumenti' }],
  'Guerriero': [{ name: 'Cotta di Maglia', qty: 1, category: 'Armature' }, { name: 'Spada Lunga', qty: 1, category: 'Armi' }, { name: 'Scudo', qty: 1, category: 'Armature' }, { name: 'Balestra Leggera', qty: 1, category: 'Armi' }],
  'Ladro': [{ name: 'Armatura di Cuoio', qty: 1, category: 'Armature' }, { name: 'Spada Corta', qty: 1, category: 'Armi' }, { name: 'Arco Corto', qty: 1, category: 'Armi' }, { name: 'Pugnale', qty: 2, category: 'Armi' }, { name: 'Arnesi da Scasso', qty: 1, category: 'Kit e Strumenti' }],
  'Mago': [{ name: 'Pugnale', qty: 1, category: 'Armi' }, { name: 'Libro degli Incantesimi', qty: 1, category: 'Varie' }],
  'Monaco': [{ name: 'Spada Corta', qty: 1, category: 'Armi' }],
  'Paladino': [{ name: 'Cotta di Maglia', qty: 1, category: 'Armature' }, { name: 'Spada Lunga', qty: 1, category: 'Armi' }, { name: 'Scudo', qty: 1, category: 'Armature' }],
  'Ranger': [{ name: 'Corazza di Scaglie', qty: 1, category: 'Armature' }, { name: 'Spada Corta', qty: 2, category: 'Armi' }, { name: 'Arco Lungo', qty: 1, category: 'Armi' }],
  'Stregone': [{ name: 'Balestra Leggera', qty: 1, category: 'Armi' }, { name: 'Pugnale', qty: 2, category: 'Armi' }],
  'Warlock': [{ name: 'Armatura di Cuoio', qty: 1, category: 'Armature' }, { name: 'Balestra Leggera', qty: 1, category: 'Armi' }, { name: 'Pugnale', qty: 2, category: 'Armi' }]
};

export const BG_START = {
  'Accolito': [{ name: 'Simbolo Sacro', qty: 1, category: 'Varie' }, { name: "Bastoncini d'Incenso", qty: 5, category: 'Varie' }],
  'Ciarlatano': [{ name: 'Kit da Falsario', qty: 1, category: 'Kit e Strumenti' }, { name: 'Kit da Trucco', qty: 1, category: 'Kit e Strumenti' }],
  'Criminale': [{ name: 'Arnesi da Scasso', qty: 1, category: 'Kit e Strumenti' }, { name: 'Piede di Porco', qty: 1, category: 'Varie' }],
  'Intrattenitore': [{ name: 'Strumento Musicale', qty: 1, category: 'Kit e Strumenti' }, { name: 'Kit da Trucco', qty: 1, category: 'Kit e Strumenti' }],
  'Eroe Popolano': [{ name: 'Strumenti da Artigiano', qty: 1, category: 'Kit e Strumenti' }],
  'Artigiano di Gilda': [{ name: 'Strumenti da Artigiano', qty: 1, category: 'Kit e Strumenti' }],
  'Eremita': [{ name: 'Kit da Erborista', qty: 1, category: 'Kit e Strumenti' }],
  'Nobile': [{ name: 'Anello con Sigillo', qty: 1, category: 'Varie' }],
  'Forestiero': [{ name: 'Strumento Musicale', qty: 1, category: 'Kit e Strumenti' }],
  'Sapiente': [],
  'Marinaio': [{ name: 'Strumenti da Navigatore', qty: 1, category: 'Kit e Strumenti' }, { name: 'Corda di Seta', qty: 1, category: 'Varie' }],
  'Soldato': [{ name: 'Insegna di Grado', qty: 1, category: 'Varie' }],
  'Monello': [{ name: 'Kit da Trucco', qty: 1, category: 'Kit e Strumenti' }, { name: 'Arnesi da Scasso', qty: 1, category: 'Kit e Strumenti' }]
};

