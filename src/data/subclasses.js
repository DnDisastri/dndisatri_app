// === SOTTOCLASSI DA TUTTI I MANUALI (nomi = titoli; descrizioni = sintesi originali) ===

export const ALL_SUBCLASSES = {
  'Barbaro': [
    { name: 'Cammino del Berserker', desc: 'Furia sanguinaria: attacchi extra a costo della stanchezza.' },
    { name: 'Cammino del Totem Guerriero', desc: 'Spiriti animali che donano resistenza e doni mistici.' },
    { name: 'Cammino del Guardiano Ancestrale', desc: 'Gli antenati proteggono te e i tuoi alleati.' },
    { name: 'Cammino dello Zelota', desc: 'Fervore divino: danni sacri e ritorno dalla morte.' },
    { name: "Cammino dell'Araldo delle Tempeste", desc: 'Aure elementali che colpiscono chi ti circonda.' },
    { name: 'Cammino della Bestia', desc: 'In ira ti spuntano artigli, zanne o coda.' },
    { name: 'Cammino della Magia Selvaggia', desc: 'Effetti magici imprevedibili scatenati dalla furia.' },
    { name: 'Cammino dei Giganti', desc: 'Attingi al potere dei giganti, crescendo di taglia.' }
  ],
  'Bardo': [
    { name: 'Collegio della Sapienza', desc: 'Colto e versatile: ruba incantesimi e potenzia le abilità.' },
    { name: 'Collegio del Valore', desc: 'Bardo guerriero: armature e attacchi extra.' },
    { name: 'Collegio delle Spade', desc: 'Acrobazie di lama e danni in mischia.' },
    { name: 'Collegio dei Sussurri', desc: 'Manipolazione e paura, quasi un assassino.' },
    { name: "Collegio dell'Incanto", desc: 'Fascino irresistibile e presenza magnetica.' },
    { name: 'Collegio della Creazione', desc: 'Canti che danno forma e vita alla realtà.' },
    { name: "Collegio dell'Eloquenza", desc: 'Oratore perfetto: persuasione quasi infallibile.' }
  ],
  'Chierico': [
    { name: 'Dominio della Conoscenza', desc: 'Sapere e divinazione; competenze potenziate.' },
    { name: 'Dominio della Vita', desc: 'Il miglior guaritore: cure aumentate.' },
    { name: 'Dominio della Guerra', desc: 'Chierico da battaglia: attacchi divini extra.' },
    { name: 'Dominio della Tempesta', desc: 'Fulmini e tuoni contro chi ti colpisce.' },
    { name: "Dominio dell'Inganno", desc: 'Illusioni, furtività e colpi a tradimento.' },
    { name: 'Dominio della Luce', desc: 'Fuoco radioso e protezione dalle tenebre.' },
    { name: 'Dominio della Natura', desc: 'Incantesimi druidici e dominio sugli animali.' },
    { name: 'Dominio della Morte', desc: 'Necromanzia e danni potenziati.' },
    { name: 'Dominio della Forgia', desc: 'Benedice armi e armature; resistenza al fuoco.' },
    { name: 'Dominio della Tomba', desc: 'Confine tra vita e morte; nega i colpi critici.' },
    { name: "Dominio dell'Ordine", desc: 'Autorità e comando; incanti di controllo.' },
    { name: 'Dominio della Pace', desc: 'Legami protettivi che uniscono il gruppo.' },
    { name: 'Dominio del Crepuscolo', desc: 'Scurovisione e riposo per gli alleati nel buio.' },
    { name: 'Dominio Arcano', desc: 'Fonde magia arcana e divina.' }
  ],
  'Druido': [
    { name: 'Circolo della Terra', desc: 'Incantatore versatile legato a un ambiente.' },
    { name: 'Circolo della Luna', desc: 'Forme selvatiche potenti da mutaforma guerriero.' },
    { name: 'Circolo dei Sogni', desc: 'Magia fatata: cure e brevi teletrasporti.' },
    { name: 'Circolo del Pastore', desc: 'Evoca e potenzia bestie e spiriti.' },
    { name: 'Circolo delle Spore', desc: 'Necromanzia fungina: combatti in mischia.' },
    { name: 'Circolo delle Stelle', desc: 'Forme costellate per attacco, cura o sapere.' },
    { name: 'Circolo del Fuoco Selvaggio', desc: 'Uno spirito di fuoco che cura e brucia.' }
  ],
  'Guerriero': [
    { name: 'Campione', desc: 'Combattente diretto: critici più frequenti e atletismo.' },
    { name: 'Maestro di Battaglia', desc: 'Manovre tattiche che controllano lo scontro.' },
    { name: 'Cavaliere Mistico', desc: 'Fonde combattimento e magia arcana (incantatore 1/3).' },
    { name: 'Cavaliere', desc: 'Difensore a cavallo che protegge gli alleati.' },
    { name: 'Samurai', desc: 'Determinazione ferrea e raffiche di attacchi.' },
    { name: 'Arciere Arcano', desc: 'Frecce infuse di magia con effetti speciali.' },
    { name: 'Bannereto', desc: 'Comandante che ispira e cura i compagni.' },
    { name: 'Guerriero Psionico', desc: 'Poteri mentali che potenziano attacco e difesa.' },
    { name: 'Cavaliere Runico', desc: 'Rune giganti che ingrandiscono e rinforzano.' },
    { name: "Cavaliere dell'Eco", desc: "Evoca un'eco spettrale di sé per colpire e spostarsi." }
  ],
  'Ladro': [
    { name: 'Ladro', desc: 'Scassinatore agile: usa oggetti e fugge in fretta.' },
    { name: 'Assassino', desc: 'Colpi mortali contro bersagli impreparati.' },
    { name: 'Furfante Arcano', desc: 'Ladro con illusione e inganno (incantatore 1/3).' },
    { name: 'Mente Superiore', desc: 'Stratega e falsario, colpisce aiutando gli altri.' },
    { name: 'Spadaccino', desc: 'Duellante mobile e carismatico.' },
    { name: 'Esploratore', desc: 'Ricognitore mobile, esperto di natura.' },
    { name: 'Inquisitore', desc: 'Investigatore che smaschera bugie e debolezze.' },
    { name: 'Fantasma', desc: 'Tocca la morte: danni necrotici e spettri.' },
    { name: "Lama dell'Anima", desc: 'Lame psichiche e poteri telepatici.' }
  ],
  'Mago': [
    { name: 'Abiurazione', desc: 'Scudo arcano protettivo che assorbe i danni.' },
    { name: 'Ammaliamento', desc: 'Controllo mentale e fascino.' },
    { name: 'Divinazione', desc: 'Prevede gli eventi e altera i tiri di dado.' },
    { name: 'Evocazione', desc: 'Crea e piazza aree di effetto con precisione.' },
    { name: 'Illusione', desc: 'Inganni sensoriali sempre più reali.' },
    { name: 'Invocazione', desc: 'Danni magici massimizzati.' },
    { name: 'Necromanzia', desc: 'Comanda i non morti e drena la vita.' },
    { name: 'Trasmutazione', desc: 'Altera materia e forma delle cose.' },
    { name: 'Magia da Guerra', desc: 'Equilibrio tra difesa e potenza offensiva.' },
    { name: 'Canto di Lama', desc: 'Mago spadaccino agile e difensivo.' },
    { name: 'Cronurgia', desc: 'Manipola il tempo in battaglia.' },
    { name: 'Graviturgia', desc: 'Piega la gravità per attrarre o schiacciare.' },
    { name: 'Ordine degli Scribi', desc: 'Il libro degli incantesimi prende vita.' }
  ],
  'Monaco': [
    { name: 'Via della Mano Aperta', desc: 'Arti marziali pure: spinte, atterramenti e cura.' },
    { name: "Via dell'Ombra", desc: 'Furtività, teletrasporti e oscurità.' },
    { name: 'Via dei Quattro Elementi', desc: 'Incanala gli elementi nelle arti marziali.' },
    { name: "Via dell'Anima Solare", desc: 'Raggi di energia radiante a distanza.' },
    { name: 'Via del Kensei', desc: 'Maestria con armi scelte.' },
    { name: 'Via del Maestro Ebbro', desc: 'Stile imprevedibile e schivate acrobatiche.' },
    { name: 'Via della Lunga Morte', desc: 'Attinge alla morte per resistere e spaventare.' },
    { name: 'Via della Misericordia', desc: 'Cura o infligge sofferenza con il tocco.' },
    { name: 'Via del Sé Astrale', desc: "Un'entità spirituale combatte con te." }
  ],
  'Paladino': [
    { name: 'Giuramento di Devozione', desc: 'Cavaliere sacro: onore e protezione.' },
    { name: 'Giuramento degli Antichi', desc: 'Difensore della luce e della natura.' },
    { name: 'Giuramento di Vendetta', desc: 'Cacciatore implacabile del male.' },
    { name: 'Giuramento della Corona', desc: 'Fedele alla civiltà: tiene il fronte.' },
    { name: 'Giuramento della Conquista', desc: 'Domina con il terrore e la forza.' },
    { name: 'Giuramento della Redenzione', desc: 'Cerca la pace, la violenza per ultima.' },
    { name: 'Giuramento della Gloria', desc: 'Eroe atletico che spinge il gruppo.' },
    { name: 'Giuramento dei Guardiani', desc: 'Caccia le minacce extraplanari.' },
    { name: 'Spezzagiuramenti', desc: 'Paladino caduto votato al male.' }
  ],
  'Ranger': [
    { name: 'Cacciatore', desc: 'Specialista contro orde o grandi bestie.' },
    { name: 'Signore delle Bestie', desc: 'Combatte al fianco di un compagno animale.' },
    { name: 'Vagabondo Fatato', desc: 'Magia fatata: incanti e spostamenti.' },
    { name: "Cacciatore nell'Ombra", desc: 'Agguati nel buio, letale nella sorpresa.' },
    { name: "Viandante dell'Orizzonte", desc: 'Guerriero planare che teletrasporta.' },
    { name: 'Sterminatore di Mostri', desc: 'Duellante che prevede e punisce i mostri.' },
    { name: 'Custode di Sciami', desc: 'Uno sciame di creature lo aiuta in battaglia.' }
  ],
  'Stregone': [
    { name: 'Discendenza Draconica', desc: 'Sangue di drago: più PF e danni elementali.' },
    { name: 'Magia Selvaggia', desc: 'Potere caotico e imprevedibile.' },
    { name: 'Anima Divina', desc: 'Magia sacra: cure e resistenza.' },
    { name: 'Magia delle Ombre', desc: 'Oscurità, paura e un mastino spettrale.' },
    { name: 'Stregoneria della Tempesta', desc: 'Vola e scatena vento e fulmini.' },
    { name: 'Mente Aberrante', desc: 'Poteri psionici e telepatia.' },
    { name: 'Anima Meccanica', desc: 'Ordine cosmico: precisione e protezione.' }
  ],
  'Warlock': [
    { name: "Patrono: l'Immondo", desc: 'Patto infernale: PF temporanei e fuoco.' },
    { name: "Patrono: l'Arcifata", desc: 'Incanto fatato: fascino e fughe.' },
    { name: 'Patrono: il Grande Antico', desc: 'Orrori psichici e telepatia.' },
    { name: 'Patrono: la Lama Maledetta', desc: 'Guerriero occulto con arma legata.' },
    { name: "Patrono: l'Imperituro", desc: 'Sfida la morte, difficile da abbattere.' },
    { name: 'Patrono: il Genio', desc: 'Doni elementali da un potente genio.' },
    { name: 'Patrono: le Profondità', desc: 'Poteri abissali e tentacoli.' },
    { name: 'Patrono: il Non Morto', desc: 'Legato a un lich o vampiro: orrore e resistenza.' }
  ]
};

