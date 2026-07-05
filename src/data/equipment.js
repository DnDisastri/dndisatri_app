// === EQUIPAGGIAMENTO INIZIALE PER CLASSE (scelte A/B = fatti di gioco) ===

export const CLASS_EQUIP = {
  'Barbaro': { fixed: [], choices: [
    { label: 'Arma principale', options: [{ label: 'Ascia Bipenne', items: [{ name: 'Ascia Bipenne', category: 'Armi' }] }, { label: 'Arma da guerra (Spada Lunga)', items: [{ name: 'Spada Lunga', category: 'Armi' }] }] },
    { label: 'Armi secondarie', options: [{ label: 'Due Asce da Battaglia', items: [{ name: 'Ascia da Battaglia', category: 'Armi', qty: 2 }] }, { label: 'Arma semplice (Lancia)', items: [{ name: 'Lancia', category: 'Armi' }] }] }
  ] },
  'Bardo': { fixed: [{ name: 'Armatura di Cuoio', category: 'Armature' }, { name: 'Pugnale', category: 'Armi' }, { name: 'Strumento Musicale', category: 'Kit e Strumenti' }], choices: [
    { label: 'Arma', options: [{ label: 'Stocco (Spada Corta)', items: [{ name: 'Spada Corta', category: 'Armi' }] }, { label: 'Spada Lunga', items: [{ name: 'Spada Lunga', category: 'Armi' }] }] }
  ] },
  'Chierico': { fixed: [{ name: 'Scudo', category: 'Armature' }, { name: 'Simbolo Sacro', category: 'Varie' }], choices: [
    { label: 'Arma', options: [{ label: 'Mazza', items: [{ name: 'Mazza', category: 'Armi' }] }, { label: 'Martello da Guerra', items: [{ name: 'Martello da Guerra', category: 'Armi' }] }] },
    { label: 'Armatura', options: [{ label: 'Corazza di Scaglie', items: [{ name: 'Corazza di Scaglie', category: 'Armature' }] }, { label: 'Armatura di Cuoio', items: [{ name: 'Armatura di Cuoio', category: 'Armature' }] }] },
    { label: 'A distanza', options: [{ label: 'Balestra Leggera', items: [{ name: 'Balestra Leggera', category: 'Armi' }] }, { label: 'Arma semplice (Mazza)', items: [{ name: 'Mazza', category: 'Armi' }] }] }
  ] },
  'Druido': { fixed: [{ name: 'Armatura di Cuoio', category: 'Armature' }, { name: 'Kit da Erborista', category: 'Kit e Strumenti' }], choices: [
    { label: 'Scudo o arma', options: [{ label: 'Scudo di legno (Scudo)', items: [{ name: 'Scudo', category: 'Armature' }] }, { label: 'Arma semplice (Lancia)', items: [{ name: 'Lancia', category: 'Armi' }] }] },
    { label: 'Arma da mischia', options: [{ label: 'Lancia', items: [{ name: 'Lancia', category: 'Armi' }] }, { label: 'Mazza', items: [{ name: 'Mazza', category: 'Armi' }] }] }
  ] },
  'Guerriero': { fixed: [], choices: [
    { label: 'Armatura', options: [{ label: 'Cotta di Maglia', items: [{ name: 'Cotta di Maglia', category: 'Armature' }] }, { label: 'Cuoio + Arco Lungo', items: [{ name: 'Armatura di Cuoio', category: 'Armature' }, { name: 'Arco Lungo', category: 'Armi' }] }] },
    { label: 'Arma principale', options: [{ label: 'Arma da guerra + Scudo', items: [{ name: 'Spada Lunga', category: 'Armi' }, { name: 'Scudo', category: 'Armature' }] }, { label: 'Due armi da guerra', items: [{ name: 'Spada Lunga', category: 'Armi' }, { name: 'Ascia da Battaglia', category: 'Armi' }] }] },
    { label: 'A distanza', options: [{ label: 'Balestra Leggera', items: [{ name: 'Balestra Leggera', category: 'Armi' }] }, { label: 'Due Asce da Battaglia', items: [{ name: 'Ascia da Battaglia', category: 'Armi', qty: 2 }] }] }
  ] },
  'Ladro': { fixed: [{ name: 'Armatura di Cuoio', category: 'Armature' }, { name: 'Pugnale', category: 'Armi', qty: 2 }, { name: 'Arnesi da Scasso', category: 'Kit e Strumenti' }], choices: [
    { label: 'Arma principale', options: [{ label: 'Stocco (Spada Corta)', items: [{ name: 'Spada Corta', category: 'Armi' }] }, { label: 'Spada Corta', items: [{ name: 'Spada Corta', category: 'Armi' }] }] },
    { label: 'A distanza', options: [{ label: 'Arco Corto', items: [{ name: 'Arco Corto', category: 'Armi' }] }, { label: 'Spada Corta', items: [{ name: 'Spada Corta', category: 'Armi' }] }] }
  ] },
  'Mago': { fixed: [{ name: 'Libro degli Incantesimi', category: 'Varie' }, { name: 'Focus Arcano', category: 'Varie' }], choices: [
    { label: 'Arma', options: [{ label: 'Bastone Ferrato (Pugnale)', items: [{ name: 'Pugnale', category: 'Armi' }] }, { label: 'Pugnale', items: [{ name: 'Pugnale', category: 'Armi' }] }] }
  ] },
  'Monaco': { fixed: [], choices: [
    { label: 'Arma', options: [{ label: 'Spada Corta', items: [{ name: 'Spada Corta', category: 'Armi' }] }, { label: 'Arma semplice (Lancia)', items: [{ name: 'Lancia', category: 'Armi' }] }] }
  ] },
  'Paladino': { fixed: [{ name: 'Cotta di Maglia', category: 'Armature' }, { name: 'Simbolo Sacro', category: 'Varie' }], choices: [
    { label: 'Arma principale', options: [{ label: 'Arma da guerra + Scudo', items: [{ name: 'Spada Lunga', category: 'Armi' }, { name: 'Scudo', category: 'Armature' }] }, { label: 'Due armi da guerra', items: [{ name: 'Spada Lunga', category: 'Armi' }, { name: 'Ascia da Battaglia', category: 'Armi' }] }] },
    { label: 'Secondaria', options: [{ label: 'Giavellotti (Lancia)', items: [{ name: 'Lancia', category: 'Armi' }] }, { label: 'Arma da mischia semplice (Mazza)', items: [{ name: 'Mazza', category: 'Armi' }] }] }
  ] },
  'Ranger': { fixed: [{ name: 'Arco Lungo', category: 'Armi' }], choices: [
    { label: 'Armatura', options: [{ label: 'Corazza di Scaglie', items: [{ name: 'Corazza di Scaglie', category: 'Armature' }] }, { label: 'Armatura di Cuoio', items: [{ name: 'Armatura di Cuoio', category: 'Armature' }] }] },
    { label: 'Armi da mischia', options: [{ label: 'Due Spade Corte', items: [{ name: 'Spada Corta', category: 'Armi', qty: 2 }] }, { label: 'Due Lance', items: [{ name: 'Lancia', category: 'Armi', qty: 2 }] }] }
  ] },
  'Stregone': { fixed: [{ name: 'Focus Arcano', category: 'Varie' }, { name: 'Pugnale', category: 'Armi', qty: 2 }], choices: [
    { label: 'Arma', options: [{ label: 'Balestra Leggera', items: [{ name: 'Balestra Leggera', category: 'Armi' }] }, { label: 'Arma semplice (Mazza)', items: [{ name: 'Mazza', category: 'Armi' }] }] }
  ] },
  'Warlock': { fixed: [{ name: 'Armatura di Cuoio', category: 'Armature' }, { name: 'Focus Arcano', category: 'Varie' }, { name: 'Pugnale', category: 'Armi', qty: 2 }], choices: [
    { label: 'Arma', options: [{ label: 'Balestra Leggera', items: [{ name: 'Balestra Leggera', category: 'Armi' }] }, { label: 'Arma semplice (Mazza)', items: [{ name: 'Mazza', category: 'Armi' }] }] }
  ] }
};

