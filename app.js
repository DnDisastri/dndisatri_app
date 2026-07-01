// === FIREBASE CONFIGURATION ===
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { 
  getAuth, 
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  updateProfile,
  sendPasswordResetEmail
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { 
  getFirestore,
  collection,
  addDoc,
  setDoc,
  getDocs,
  getDoc,
  doc,
  updateDoc,
  deleteDoc,
  query,
  where,
  orderBy,
  serverTimestamp,
  onSnapshot,
  arrayUnion,
  arrayRemove,
  runTransaction,
  writeBatch
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// Firebase config
const firebaseConfig = {
  apiKey: "AIzaSyB5qd1oSlt1lWIFF2s1ahja2L2g0NHK7H0",
  authDomain: "dndisastri-app.firebaseapp.com",
  projectId: "dndisastri-app",
  storageBucket: "dndisastri-app.firebasestorage.app",
  messagingSenderId: "280100445917",
  appId: "1:280100445917:web:5a3fccf91f1ba492754534"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

// === GLOBAL STATE ===
let currentUser = null;
let currentUserRole = 'player';
let currentUsername = '';
let currentCharacters = [];
let currentQuests = [];
let fallenHeroes = [];
let pendingChanges = [];
let hasActiveCharacter = false;
let guildCharacters = [];
let guildUsers = {};
let levelUpCtx = null;
let currentUserPhoto = '';
let marketItems = [];
let notifications = [];
let currentCampaignId = null;
let campaignsCache = [];

// === UTILITY FUNCTIONS ===
function calculateModifier(score) {
  return Math.floor((score - 10) / 2);
}

function formatModifier(mod) {
  return mod >= 0 ? `+${mod}` : `${mod}`;
}

// === LEVEL UP / ASI / DADO VITA ===
const ASI_LEVELS = [4, 8, 12, 16, 19];
const HIT_DICE = [6, 8, 10, 12];

function isASILevel(level) {
  return ASI_LEVELS.includes(level);
}

// PF guadagnati salendo di UN livello (dal 2° in poi).
// Metodo "media" 5e: (dado/2 + 1) + mod COS, minimo 1 per livello.
function hpGainForLevel(hitDie, conMod) {
  return Math.max(1, Math.floor(hitDie / 2) + 1 + conMod);
}

// === OGGETTI MAGICI: CARATTERISTICHE EFFICACI ===
// char.stats = punteggi BASE (creazione + ASI). char.itemEffects = effetti temporanei
// da oggetti magici: { name, ability, mode: 'set'|'bonus', value }.
// 'set' porta il punteggio a `value` (solo se migliore, come da regole 5e);
// 'bonus' somma `value` (può essere negativo). Restituisce i punteggi EFFICACI.
function effectiveStats(char) {
  const stats = Object.assign({ str: 10, dex: 10, con: 10, int: 10, wis: 10, cha: 10 }, char.stats || {});
  (char.itemEffects || []).forEach(e => {
    if (!e || !(e.ability in stats)) return;
    if (e.mode === 'set') {
      stats[e.ability] = Math.max(stats[e.ability], e.value);
    } else {
      stats[e.ability] = stats[e.ability] + (e.value || 0);
    }
  });
  return stats;
}

const STAT_LABELS = { str: 'FOR', dex: 'DES', con: 'COS', int: 'INT', wis: 'SAG', cha: 'CAR' };

// PF massimi EFFICACI: se un oggetto magico modifica la COS, i PF massimi
// salgono/scendono di (Δmod COS) × livello finché l'oggetto è equipaggiato.
// Non modifica il valore salvato (reversibile alla rimozione dell'oggetto).
function effectiveHpMax(char) {
  const combat = char.combat || {};
  const baseHp = combat.hpMax || 0;
  const level = char.level || 1;
  const baseConMod = calculateModifier((char.stats && char.stats.con) || 10);
  const effConMod = calculateModifier(effectiveStats(char).con);
  return baseHp + (effConMod - baseConMod) * level;
}

// ===================================================================
// PATCH PER APP.JS - Aggiungere dopo le funzioni utility esistenti
// ===================================================================

// === CALCOLI BONUS COMPETENZA ===
function getProficiencyBonus(level) {
  if (level >= 17) return 6;
  if (level >= 13) return 5;
  if (level >= 9) return 4;
  if (level >= 5) return 3;
  return 2;
}

// === CALCOLI SPELL DC E BONUS ===
function calculateSpellDC(spellAbility, profBonus, stats) {
  if (!spellAbility || !stats) return 0;
  const abilityMod = calculateModifier(stats[spellAbility] || 10);
  return 8 + abilityMod + profBonus;
}

function calculateSpellAttackBonus(spellAbility, profBonus, stats) {
  if (!spellAbility || !stats) return 0;
  const abilityMod = calculateModifier(stats[spellAbility] || 10);
  return abilityMod + profBonus;
}

// === CALCOLI WEAPON ATTACK BONUS ===
function calculateWeaponAttackBonus(attackStat, weaponBonus, profBonus, stats) {
  if (!attackStat || !stats) return 0;
  const abilityMod = calculateModifier(stats[attackStat] || 10);
  return abilityMod + profBonus + (weaponBonus || 0);
}

// === EXPORT PDF ===
async function exportCharacterToPDF(char) {
  // Carica jsPDF da CDN se non già caricato
  if (!window.jspdf) {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    document.head.appendChild(script);
    await new Promise(resolve => script.onload = resolve);
  }
  
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  const stats = effectiveStats(char);
  const combat = char.combat || {};
  const weapons = char.weapons || [];
  const skills = char.skills || {};
  const savingThrows = char.savingThrows || {};
  const profBonus = char.proficiencyBonus || calculateProficiencyBonus(char.level || 1);
  
  let y = 20;
  const lineHeight = 6;
  const pageWidth = doc.internal.pageSize.getWidth();
  
  // === HEADER CON LOGO ===
  try {
    // Aggiungi logo (se disponibile)
    const logoImg = new Image();
    logoImg.src = '/logo.jpg';
    await new Promise((resolve, reject) => {
      logoImg.onload = resolve;
      logoImg.onerror = () => resolve(); // Continua anche se logo fallisce
      setTimeout(resolve, 1000); // Timeout dopo 1s
    });
    
    if (logoImg.complete && logoImg.naturalHeight !== 0) {
      doc.addImage(logoImg, 'JPEG', 10, 10, 30, 30);
    }
  } catch (e) {
    console.log('Logo non caricato, continuo senza');
  }
  
  // Titolo con box colorato
  doc.setFillColor(212, 66, 62); // Rosso D&Disastri
  doc.rect(45, 15, pageWidth - 55, 20, 'F');
  doc.setTextColor(255, 255, 255);
  doc.setFontSize(22);
  doc.setFont(undefined, 'bold');
  doc.text(char.name, pageWidth / 2, 27, { align: 'center' });
  
  // Reset colore
  doc.setTextColor(0, 0, 0);
  
  y = 45;
  doc.setFontSize(11);
  doc.setFont(undefined, 'normal');
  
  // === INFORMAZIONI BASE ===
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.setTextColor(44, 62, 110);
  doc.text('INFORMAZIONI BASE', 15, y);
  y += 7;
  
  doc.setFontSize(10);
  doc.setFont(undefined, 'normal');
  doc.setTextColor(0, 0, 0);
  doc.text(`Classe: ${char.class}${char.subclass ? ' (' + char.subclass + ')' : ''}`, 15, y);
  doc.text(`Livello: ${char.level || 1}`, 110, y);
  y += lineHeight;
  doc.text(`Razza: ${char.race}`, 15, y);
  if (char.background) {
    doc.text(`Background: ${char.background}`, 110, y);
  }
  y += lineHeight + 3;
  
  // === CARATTERISTICHE ===
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.setTextColor(44, 62, 110);
  doc.text('CARATTERISTICHE', 15, y);
  y += 7;
  
  doc.setFontSize(10);
  doc.setFont(undefined, 'normal');
  doc.setTextColor(0, 0, 0);
  
  const statNames = { str: 'FOR', dex: 'DES', con: 'COS', int: 'INT', wis: 'SAG', cha: 'CAR' };
  let statY = y;
  let col = 0;
  for (const [key, label] of Object.entries(statNames)) {
    const value = stats[key] || 10;
    const mod = calculateModifier(value);
    const x = 15 + (col * 65);
    
    // Box per ogni caratteristica
    doc.setDrawColor(212, 66, 62);
    doc.rect(x, statY - 4, 30, 8);
    doc.text(`${label}`, x + 2, statY);
    doc.setFont(undefined, 'bold');
    doc.text(`${value}`, x + 15, statY);
    doc.setFont(undefined, 'normal');
    doc.text(`(${formatModifier(mod)})`, x + 22, statY);
    
    col++;
    if (col === 3) {
      col = 0;
      statY += 10;
    }
  }
  y = statY + 5;
  
  // === COMBATTIMENTO ===
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.setTextColor(44, 62, 110);
  doc.text('COMBATTIMENTO', 15, y);
  y += 7;
  
  doc.setFontSize(10);
  doc.setFont(undefined, 'normal');
  doc.setTextColor(0, 0, 0);
  
  doc.text(`CA: ${combat.ac || 10}`, 15, y);
  doc.text(`Iniziativa: ${formatModifier(combat.initiative || 0)}`, 55, y);
  doc.text(`Velocità: ${combat.speed || 30} ft`, 110, y);
  doc.text(`PF Max: ${effectiveHpMax(char)}`, 155, y);
  y += lineHeight;
  doc.text(`Bonus Competenza: +${profBonus}`, 15, y);
  y += lineHeight + 3;
  
  // === TIRI SALVEZZA ===
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.setTextColor(44, 62, 110);
  doc.text('TIRI SALVEZZA', 15, y);
  y += 7;
  
  doc.setFontSize(10);
  doc.setFont(undefined, 'normal');
  doc.setTextColor(0, 0, 0);
  
  let saveCol = 0;
  let saveY = y;
  for (const [ability, label] of Object.entries(statNames)) {
    const isProficient = savingThrows[ability] || false;
    const bonus = calculateSavingThrow(stats, ability, isProficient, profBonus);
    const x = 15 + (saveCol * 65);
    
    if (isProficient) {
      // Marker chiaro per competenza
      doc.setFillColor(76, 175, 80); // Verde
      doc.circle(x, saveY - 1.5, 1.5, 'F');
    }
    doc.text(`${label}: ${formatModifier(bonus)}`, x + 4, saveY);
    
    saveCol++;
    if (saveCol === 3) {
      saveCol = 0;
      saveY += lineHeight;
    }
  }
  y = saveY + 5;
  
  // === ARMI ===
  if (weapons.length > 0) {
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(44, 62, 110);
    doc.text('ARMI & ATTACCHI', 15, y);
    y += 7;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(0, 0, 0);
    
    weapons.forEach(weapon => {
      const attackBonus = calculateWeaponAttackBonus(
        weapon.attackStat, weapon.weaponBonus, profBonus, stats
      );
      doc.text(`${weapon.name}`, 15, y);
      doc.text(`Attacco: +${attackBonus}`, 80, y);
      doc.text(`Danni: ${weapon.damage}`, 130, y);
      y += lineHeight;
    });
    y += 3;
  }
  
  // === ABILITÀ (SKILLS) ===
  if (y > 220) {
    doc.addPage();
    y = 20;
  }
  
  doc.setFontSize(14);
  doc.setFont(undefined, 'bold');
  doc.setTextColor(44, 62, 110);
  doc.text('ABILITA (SKILLS)', 15, y);
  y += 7;
  
  doc.setFontSize(9);
  doc.setFont(undefined, 'normal');
  doc.setTextColor(0, 0, 0);
  
  let skillCol = 0;
  let skillY = y;
  Object.keys(SKILLS_MAP).forEach(skill => {
    const skillData = skills[skill] || { proficient: false, expertise: false };
    const bonus = calculateSkillBonus(stats, skill, skillData.proficient, skillData.expertise, profBonus);
    const x = 15 + (skillCol * 95);
    
    // Marker visibile per competenza/expertise
    if (skillData.expertise) {
      // Due pallini arancioni per expertise
      doc.setFillColor(255, 152, 0);
      doc.circle(x, skillY - 1.5, 1.2, 'F');
      doc.circle(x + 3, skillY - 1.5, 1.2, 'F');
      doc.text(`${SKILLS_ITALIAN[skill]}: ${formatModifier(bonus)}`, x + 7, skillY);
    } else if (skillData.proficient) {
      // Un pallino verde per competenza
      doc.setFillColor(76, 175, 80);
      doc.circle(x, skillY - 1.5, 1.2, 'F');
      doc.text(`${SKILLS_ITALIAN[skill]}: ${formatModifier(bonus)}`, x + 4, skillY);
    } else {
      // Nessun marker
      doc.text(`${SKILLS_ITALIAN[skill]}: ${formatModifier(bonus)}`, x, skillY);
    }
    
    skillCol++;
    if (skillCol === 2) {
      skillCol = 0;
      skillY += lineHeight;
    }
  });
  y = skillY + 5;
  
  // === INCANTESIMI ===
  const spellcasting = char.spellcasting || {};
  
  // Check se ha incantesimi (vecchio formato char.spells o nuovo spellcasting)
  const hasSpells = char.spellAbility || char.spells || 
                    spellcasting.ability || spellcasting.cantrips || 
                    Object.keys(spellcasting).some(k => k.startsWith('level'));
  
  if (hasSpells) {
    if (y > 230) {
      doc.addPage();
      y = 20;
    }
    
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(44, 62, 110);
    doc.text('INCANTESIMI', 15, y);
    y += 7;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(0, 0, 0);
    
    // Caratteristica incantesimi (vecchio o nuovo formato)
    const spellAbility = char.spellAbility || spellcasting.ability;
    
    if (spellAbility) {
      doc.text(`Caratteristica Incantesimi: ${spellAbility}`, 15, y);
      y += lineHeight;
      
      // Calcola CD e Bonus Attacco se possibile
      if (char.stats) {
        const spellAbilityLower = spellAbility.toLowerCase();
        let abilityKey = null;
        
        // Map ability name to key
        const abilityMap = {
          'intelligenza': 'int', 'int': 'int',
          'saggezza': 'wis', 'sag': 'wis', 'wis': 'wis',
          'carisma': 'cha', 'car': 'cha', 'cha': 'cha'
        };
        
        abilityKey = abilityMap[spellAbilityLower];
        
        if (abilityKey && char.stats[abilityKey]) {
          const abilityMod = calculateModifier(char.stats[abilityKey]);
          const spellDC = 8 + profBonus + abilityMod;
          const spellAttack = profBonus + abilityMod;
          
          doc.text(`CD Tiro Salvezza: ${spellDC}`, 15, y);
          doc.text(`Bonus Attacco Incantesimi: ${formatModifier(spellAttack)}`, 100, y);
          y += lineHeight;
        }
      }
      y += 2;
    }
    
    // Trucchetti
    if (spellcasting.cantrips) {
      doc.setFont(undefined, 'bold');
      doc.text('Trucchetti:', 15, y);
      y += lineHeight;
      doc.setFont(undefined, 'normal');
      
      const cantripLines = doc.splitTextToSize(spellcasting.cantrips, 180);
      doc.text(cantripLines, 15, y);
      y += cantripLines.length * lineHeight + 3;
    }
    
    // Incantesimi per livello
    for (let i = 1; i <= 9; i++) {
      const levelData = spellcasting[`level${i}`];
      if (levelData && (levelData.spells || levelData.slots)) {
        if (y > 250) {
          doc.addPage();
          y = 20;
        }
        
        doc.setFont(undefined, 'bold');
        doc.text(`Livello ${i}${levelData.slots ? ` (Slot: ${levelData.slots})` : ''}:`, 15, y);
        y += lineHeight;
        doc.setFont(undefined, 'normal');
        
        if (levelData.spells) {
          const spellLines = doc.splitTextToSize(levelData.spells, 180);
          doc.text(spellLines, 15, y);
          y += spellLines.length * lineHeight + 3;
        } else {
          y += 3;
        }
      }
    }
    
    // Vecchio formato (se presente)
    if (char.spells && !spellcasting.cantrips) {
      doc.setFont(undefined, 'bold');
      doc.text('Incantesimi Conosciuti:', 15, y);
      y += lineHeight;
      doc.setFont(undefined, 'normal');
      
      const spellLines = doc.splitTextToSize(char.spells, 180);
      doc.text(spellLines, 15, y);
      y += spellLines.length * lineHeight + 5;
    }
  }
  
  // === ABILITÀ DI CLASSE ===
  if (char.classFeatures) {
    if (y > 240) {
      doc.addPage();
      y = 20;
    }
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(44, 62, 110);
    doc.text('ABILITA DI CLASSE', 15, y);
    y += 7;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(0, 0, 0);
    const lines = doc.splitTextToSize(char.classFeatures, 180);
    doc.text(lines, 15, y);
    y += lines.length * lineHeight + 5;
  }
  
  // === INVENTARIO ===
  if (char.inventory) {
    if (y > 240) {
      doc.addPage();
      y = 20;
    }
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(44, 62, 110);
    doc.text('INVENTARIO', 15, y);
    y += 7;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(0, 0, 0);
    const lines = doc.splitTextToSize(char.inventory, 180);
    doc.text(lines, 15, y);
    y += lines.length * lineHeight + 5;
  }
  
  // === NOTE ===
  if (char.notes) {
    if (y > 240) {
      doc.addPage();
      y = 20;
    }
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(44, 62, 110);
    doc.text('NOTE', 15, y);
    y += 7;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(0, 0, 0);
    const lines = doc.splitTextToSize(char.notes, 180);
    doc.text(lines, 15, y);
  }
  
  // Legenda sulla prima pagina
  doc.setPage(1);
  doc.setFontSize(7);
  doc.setTextColor(100, 100, 100);
  doc.text('Legenda: ', 15, 275);
  
  // Pallino verde
  doc.setFillColor(76, 175, 80);
  doc.circle(36, 274, 1, 'F');
  doc.text('Competente', 39, 275);
  
  // Due pallini arancioni
  doc.setFillColor(255, 152, 0);
  doc.circle(65, 274, 1, 'F');
  doc.circle(68, 274, 1, 'F');
  doc.text('Esperto', 71, 275);
  
  // Footer con logo piccolo e data
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(128, 128, 128);
    doc.text(`D&Disastri • Pagina ${i}/${pageCount} • ${new Date().toLocaleDateString('it-IT')}`, 
             pageWidth / 2, 285, { align: 'center' });
  }
  
  // Salva PDF
  doc.save(`${char.name.replace(/\s+/g, '_')}_scheda.pdf`);
}

// Aggiungi questa funzione globale
window.exportCharacterToPDF = exportCharacterToPDF;

function calculateProficiencyBonus(level) {
  return Math.floor((level - 1) / 4) + 2;
}

// Skills mapping to ability scores
const SKILLS_MAP = {
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

const SKILLS_ITALIAN = {
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

function calculateSkillBonus(stats, skill, proficient, expertise, proficiencyBonus) {
  const ability = SKILLS_MAP[skill];
  const abilityScore = stats[ability] || 10;
  const abilityMod = calculateModifier(abilityScore);
  
  let bonus = abilityMod;
  if (proficient) bonus += proficiencyBonus;
  if (expertise) bonus += proficiencyBonus;
  
  return bonus;
}

function readSkillsFromForm(prefix = 'skill') {
  const skills = {};
  Object.keys(SKILLS_MAP).forEach(skill => {
    const profElement = document.getElementById(`${prefix}-${skill}-prof`);
    const expElement = document.getElementById(`${prefix}-${skill}-exp`);
    skills[skill] = {
      proficient: profElement ? profElement.checked : false,
      expertise: expElement ? expElement.checked : false
    };
  });
  return skills;
}

function populateSkillsForm(skills, prefix = 'skill') {
  Object.keys(SKILLS_MAP).forEach(skill => {
    const profElement = document.getElementById(`${prefix}-${skill}-prof`);
    const expElement = document.getElementById(`${prefix}-${skill}-exp`);
    if (profElement) profElement.checked = skills[skill]?.proficient || false;
    if (expElement) expElement.checked = skills[skill]?.expertise || false;
  });
}

// === SAVING THROWS ===
const SAVE_NAMES = {
  str: 'Forza',
  dex: 'Destrezza',
  con: 'Costituzione',
  int: 'Intelligenza',
  wis: 'Saggezza',
  cha: 'Carisma'
};

function readSavingThrowsFromForm(prefix = 'save') {
  const savingThrows = {};
  ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(ability => {
    const element = document.getElementById(`${prefix}-${ability}`);
    savingThrows[ability] = element ? element.checked : false;
  });
  return savingThrows;
}

function populateSavingThrowsForm(savingThrows, prefix = 'save') {
  ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(ability => {
    const element = document.getElementById(`${prefix}-${ability}`);
    if (element) element.checked = savingThrows?.[ability] || false;
  });
}

function calculateSavingThrow(stats, ability, proficient, profBonus) {
  const abilityScore = stats[ability] || 10;
  const abilityMod = calculateModifier(abilityScore);
  return abilityMod + (proficient ? profBonus : 0);
}

function resetSkillsForm(prefix = 'skill') {
  Object.keys(SKILLS_MAP).forEach(skill => {
    const profElement = document.getElementById(`${prefix}-${skill}-prof`);
    const expElement = document.getElementById(`${prefix}-${skill}-exp`);
    if (profElement) profElement.checked = false;
    if (expElement) expElement.checked = false;
  });
}

// === UI HELPERS ===
function showElement(id) {
  document.getElementById(id).classList.remove('hidden');
}

function hideElement(id) {
  document.getElementById(id).classList.add('hidden');
}

function showError(elementId, message) {
  const errorEl = document.getElementById(elementId);
  if (errorEl) {
    errorEl.textContent = message;
    setTimeout(() => {
      errorEl.textContent = '';
    }, 5000);
  }
}

function showLoading() {
  showElement('loading');
}

function hideLoading() {
  hideElement('loading');
}

// === NAVIGATION ===
window.showSection = function(sectionId) {
  const sections = ['dashboard', 'characters', 'quests', 'archive', 'guild', 'maps', 'market', 'profile', 'notifications', 'audit', 'fallen', 'pending'];
  sections.forEach(id => hideElement(id));

  showElement(sectionId);

  if (sectionId === 'characters') {
    loadCharacters();
  } else if (sectionId === 'quests') {
    loadQuests();
  } else if (sectionId === 'archive') {
    loadArchivedQuests();
  } else if (sectionId === 'guild') {
    loadGuild();
  } else if (sectionId === 'maps') {
    loadMaps();
  } else if (sectionId === 'market') {
    loadMarket();
  } else if (sectionId === 'profile') {
    loadProfile();
  } else if (sectionId === 'notifications') {
    loadNotifications();
  } else if (sectionId === 'audit') {
    loadAudit();
  } else if (sectionId === 'fallen') {
    loadFallenHeroes();
  } else if (sectionId === 'pending') {
    loadPendingChanges();
  }
};

window.showLogin = function() {
  hideElement('registration');
  showElement('login');
};

window.showRegistration = function() {
  hideElement('login');
  showElement('registration');
};

// === AUTH FUNCTIONS ===
window.handleRegistration = async function(event) {
  event.preventDefault();
  
  const username = document.getElementById('reg-username').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  
  try {
    showLoading();
    
    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
    
    await updateProfile(userCredential.user, {
      displayName: username
    });
    
    // Create user document with UID as document ID
    await setDoc(doc(db, 'users', userCredential.user.uid), {
      username: username,
      email: email,
      role: 'player',
      createdAt: serverTimestamp()
    });
    
    hideLoading();
  } catch (error) {
    hideLoading();
    let errorMessage = 'Errore durante la registrazione';
    
    if (error.code === 'auth/email-already-in-use') {
      errorMessage = 'Email già in uso';
    } else if (error.code === 'auth/weak-password') {
      errorMessage = 'Password troppo debole';
    } else if (error.code === 'auth/invalid-email') {
      errorMessage = 'Email non valida';
    }
    
    showError('reg-error', errorMessage);
  }
};

window.handleLogin = async function(event) {
  event.preventDefault();
  
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  
  try {
    showLoading();
    await signInWithEmailAndPassword(auth, email, password);
    hideLoading();
  } catch (error) {
    hideLoading();
    let errorMessage = 'Errore durante l\'accesso';
    
    if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password') {
      errorMessage = 'Email o password errati';
    } else if (error.code === 'auth/invalid-email') {
      errorMessage = 'Email non valida';
    } else if (error.code === 'auth/too-many-requests') {
      errorMessage = 'Troppi tentativi. Riprova più tardi';
    }
    
    showError('login-error', errorMessage);
  }
};

window.handleForgotPassword = async function() {
  const prefill = document.getElementById('login-email').value.trim();
  const email = (prompt('Inserisci la tua email: ti invieremo un link per reimpostare la password.', prefill) || '').trim();
  if (!email) return;
  try {
    showLoading();
    await sendPasswordResetEmail(auth, email);
    hideLoading();
    alert('Email di reset inviata! Controlla la posta (anche lo spam) e segui il link.');
  } catch (error) {
    hideLoading();
    let msg = 'Errore nell\'invio dell\'email di reset';
    if (error.code === 'auth/invalid-email') msg = 'Email non valida';
    else if (error.code === 'auth/user-not-found') msg = 'Nessun account registrato con questa email';
    else if (error.code === 'auth/too-many-requests') msg = 'Troppi tentativi. Riprova più tardi';
    console.error('Errore reset password:', error);
    alert(msg);
  }
};

window.handleLogout = async function() {
  try {
    await signOut(auth);
  } catch (error) {
    console.error('Errore logout:', error);
  }
};

// === AUTH STATE OBSERVER ===
onAuthStateChanged(auth, async (user) => {
  hideLoading();
  
  if (user) {
    currentUser = user;
    
    try {
      // Get user document directly using UID
      const userDoc = await getDoc(doc(db, 'users', user.uid));
      
      if (userDoc.exists()) {
        const userData = userDoc.data();
        currentUserRole = userData.role || 'player';
        currentUsername = userData.username || user.displayName || user.email.split('@')[0];
        currentUserPhoto = userData.photo || '';
      } else {
        // User document doesn't exist yet (edge case)
        currentUserRole = 'player';
        currentUsername = user.displayName || user.email.split('@')[0];
        currentUserPhoto = '';
      }
    } catch (error) {
      console.error('Error fetching user role:', error);
      currentUserRole = 'player';
      currentUsername = user.displayName || user.email.split('@')[0];
      currentUserPhoto = '';
    }

    const roleText = currentUserRole === 'dm' ? ' (DM)' : '';
    document.getElementById('user-name').textContent = currentUsername + roleText;

    // Show/hide pending button for DMs
    // Notifiche per tutti (player e DM)
    document.getElementById('notifications-btn').style.display = 'flex';
    updateNotificationsCount();

    if (currentUserRole === 'dm') {
      document.getElementById('pending-btn').style.display = 'flex';
      document.getElementById('audit-btn').style.display = 'flex';
      updatePendingCount();
    } else {
      document.getElementById('pending-btn').style.display = 'none';
      document.getElementById('audit-btn').style.display = 'none';
    }
    
    hideElement('login');
    hideElement('registration');
    showElement('dashboard');
    showElement('logout-btn');
    
  } else {
    currentUser = null;
    currentUserRole = 'player';
    currentUsername = '';
    
    hideElement('dashboard');
    hideElement('characters');
    hideElement('quests');
    hideElement('fallen');
    hideElement('pending');
    hideElement('logout-btn');
    showElement('login');
  }
});

// === PENDING CHANGES COUNT ===
async function updatePendingCount() {
  if (currentUserRole !== 'dm') return;
  
  try {
    const q = query(
      collection(db, 'pending_changes'),
      where('status', '==', 'pending')
    );
    const snapshot = await getDocs(q);
    const count = snapshot.size;
    
    document.getElementById('pending-count').textContent = count;
    
    if (count > 0) {
      document.getElementById('pending-count').style.display = 'flex';
    } else {
      document.getElementById('pending-count').style.display = 'none';
    }
  } catch (error) {
    console.error('Error counting pending changes:', error);
  }
}

// === CHARACTERS ===
async function loadCharacters() {
  if (!currentUser) return;
  
  const listEl = document.getElementById('characters-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  
  try {
    const q = query(
      collection(db, 'characters'),
      where('userId', '==', currentUser.uid)
    );
    
    const snapshot = await getDocs(q);
    currentCharacters = [];
    hasActiveCharacter = false;
    
    snapshot.forEach(doc => {
      const charData = { id: doc.id, ...doc.data() };
      if (!charData.isDead) {
        currentCharacters.push(charData);
        hasActiveCharacter = true;
      }
    });
    
    currentCharacters.sort((a, b) => {
      if (!a.createdAt || !b.createdAt) return 0;
      return b.createdAt.seconds - a.createdAt.seconds;
    });
    
    renderCharacters();
  } catch (error) {
    console.error('Errore caricamento personaggi:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento dei personaggi</p></div>';
  }
}

function renderCharacters() {
  const listEl = document.getElementById('characters-list');
  
  if (currentCharacters.length === 0) {
    listEl.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">⚔️</div>
        <p>Non hai ancora personaggi.</p>
        <p>Crea il tuo primo eroe!</p>
      </div>
    `;
    return;
  }
  
  listEl.innerHTML = currentCharacters.map(char => `
    <div class="card" onclick="showCharacterDetail('${char.id}')">
      <h3>${char.name}</h3>
      <p><strong>Classe:</strong> ${char.class}${char.subclass ? ` (${char.subclass})` : ''}</p>
      <p><strong>Livello:</strong> ${char.level}</p>
      <p><strong>Razza:</strong> ${char.race}</p>
      <span class="card-status status-alive">⚔️ Vivo</span>
    </div>
  `).join('');
}

window.showAddCharacter = function() {
  // Players can only have one active character at a time
  if (currentUserRole === 'player' && hasActiveCharacter) {
    alert('Puoi avere solo un personaggio attivo alla volta!');
    return;
  }
  
  // Reset form
  document.getElementById('char-name').value = '';
  document.getElementById('char-class').value = '';
  document.getElementById('char-subclass').value = '';
  document.getElementById('char-race').value = '';
  document.getElementById('char-background').value = '';
  document.getElementById('char-str').value = '10';
  document.getElementById('char-dex').value = '10';
  document.getElementById('char-con').value = '10';
  document.getElementById('char-int').value = '10';
  document.getElementById('char-wis').value = '10';
  document.getElementById('char-cha').value = '10';
  document.getElementById('char-ac').value = '10';
  document.getElementById('char-initiative').value = '0';
  document.getElementById('char-speed').value = '30';
  document.getElementById('char-hp-max').value = '10';
  document.getElementById('char-class-features').value = '';
  document.getElementById('char-subclass-features').value = '';
  document.getElementById('char-inventory').value = '';
  document.getElementById('char-notes').value = '';
  
  // Reset weapons (keep only one row)
  const weaponsContainer = document.getElementById('weapons-container');
  weaponsContainer.innerHTML = `
    <div class="weapon-row">
      <input type="text" placeholder="Nome arma" class="weapon-name" />
      <select class="weapon-stat">
        <option value="">Caratteristica</option>
        <option value="str">FOR</option>
        <option value="dex">DES</option>
        <option value="cha">CAR</option>
      </select>
      <select class="weapon-bonus">
        <option value="0">+0</option>
        <option value="1">+1</option>
        <option value="2">+2</option>
        <option value="3">+3</option>
      </select>
      <input type="text" placeholder="Danni (es. 1d8+3)" class="weapon-damage" />
    </div>
  `;
  
  // Reset spellcasting
  document.getElementById('char-spell-ability').value = '';
  
  // Reset trucchetti - lascia solo un input vuoto
  const cantripsContainer = document.getElementById('cantrips-container');
  cantripsContainer.innerHTML = '<input type="text" placeholder="Nome trucchetto" class="cantrip-input" style="width: 100%; margin-bottom: 0.5rem;" />';
  
  // Reset livelli incantesimi - svuota completamente
  const spellLevelsContainer = document.getElementById('spell-levels-container');
  spellLevelsContainer.innerHTML = '';
  spellLevelCounter = 0;
  
  // Reset skills
  resetSkillsForm('skill');
  
  showElement('add-character-form');
};

window.hideAddCharacter = function() {
  hideElement('add-character-form');
};

window.handleAddCharacter = async function(event) {
  event.preventDefault();
  
  if (!currentUser) return;
  
  const level = 1;
  const skills = readSkillsFromForm('skill');
  const savingThrows = readSavingThrowsFromForm('save');
  
  // Raccogli armi
  const weapons = [];
  document.querySelectorAll('.weapon-row').forEach(row => {
    const name = row.querySelector('.weapon-name').value.trim();
    if (name) {
      weapons.push({
        name,
        attackStat: row.querySelector('.weapon-stat').value,
        weaponBonus: parseInt(row.querySelector('.weapon-bonus').value) || 0,
        damage: row.querySelector('.weapon-damage').value.trim()
      });
    }
  });
  
  const characterData = {
    userId: currentUser.uid,
    name: document.getElementById('char-name').value.trim(),
    class: document.getElementById('char-class').value.trim(),
    subclass: document.getElementById('char-subclass').value.trim(),
    race: document.getElementById('char-race').value.trim(),
    background: document.getElementById('char-background').value.trim(),
    level: level,
    hitDie: parseInt(document.getElementById('char-hitdie').value) || 8,
    feats: [],
    gp: 0,
    items: [],
    proficiencyBonus: calculateProficiencyBonus(level),
    stats: {
      str: parseInt(document.getElementById('char-str').value),
      dex: parseInt(document.getElementById('char-dex').value),
      con: parseInt(document.getElementById('char-con').value),
      int: parseInt(document.getElementById('char-int').value),
      wis: parseInt(document.getElementById('char-wis').value),
      cha: parseInt(document.getElementById('char-cha').value)
    },
    combat: {
      ac: parseInt(document.getElementById('char-ac').value),
      initiative: parseInt(document.getElementById('char-initiative').value),
      speed: parseInt(document.getElementById('char-speed').value),
      hpMax: parseInt(document.getElementById('char-hp-max').value),
      hpCurrent: parseInt(document.getElementById('char-hp-current').value) || parseInt(document.getElementById('char-hp-max').value),
      hpTemp: parseInt(document.getElementById('char-hp-temp').value) || 0
    },
    savingThrows: savingThrows,
    skills: skills,
    weapons: weapons,
    classFeatures: document.getElementById('char-class-features').value.trim(),
    subclassFeatures: document.getElementById('char-subclass-features').value.trim(),
    inventory: document.getElementById('char-inventory').value.trim(),
    notes: document.getElementById('char-notes').value.trim(),
    isDead: false,
    createdAt: serverTimestamp()
  };

  // Raccogli incantesimi
  const spellcasting = {
    ability: document.getElementById('char-spell-ability').value
  };
  
  // Raccogli trucchetti dal nuovo sistema dinamico
  const cantripInputs = document.querySelectorAll('#cantrips-container .cantrip-input');
  const cantrips = Array.from(cantripInputs)
    .map(input => input.value.trim())
    .filter(val => val !== '')
    .join(', ');
  if (cantrips) {
    spellcasting.cantrips = cantrips;
  }
  
  // Raccogli livelli incantesimi dal nuovo sistema dinamico
  const spellLevelSections = document.querySelectorAll('#spell-levels-container .spell-level-section');
  spellLevelSections.forEach(section => {
    const level = parseInt(section.dataset.level);
    const slots = parseInt(section.querySelector('.slots-input').value) || 0;
    const spellInputs = section.querySelectorAll('.spell-input');
    const spells = Array.from(spellInputs)
      .map(input => input.value.trim())
      .filter(val => val !== '')
      .join(', ');
    
    if (spells || slots) {
      spellcasting[`level${level}`] = { spells, slots };
    }
  });

  // Aggiungere ai characterData
  characterData.weapons = weapons;
  characterData.spellcasting = spellcasting;

  try {
    showLoading();
    await addDoc(collection(db, 'characters'), characterData);
    hideLoading();
    hideAddCharacter();
    loadCharacters();
  } catch (error) {
    hideLoading();
    console.error('Errore creazione personaggio:', error);
    alert('Errore nella creazione del personaggio');
  }
};

window.showCharacterDetail = function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;

  const baseStats = char.stats || {};
  const stats = effectiveStats(char); // include gli effetti degli oggetti magici
  const combat = char.combat || {};
  
  // Only DMs can kill characters
  const killButton = currentUserRole === 'dm' ? 
    `<button onclick="killCharacter('${char.id}')" class="btn-danger">💀 Dichiara Morto</button>` : '';
  
  // Players can edit their own characters (but changes need approval)
  const editButton = char.userId === currentUser.uid ?
    `<button onclick="showEditCharacter('${char.id}')" class="btn-info">✏️ Modifica</button>` : '';

  // Players can request a level up for their own (living) character; needs DM approval
  const levelUpButton = (char.userId === currentUser.uid && !char.isDead && (char.level || 1) < 20) ?
    `<button onclick="showLevelUpRequest('${char.id}')" class="btn-success">⬆️ Richiedi Passaggio di Livello</button>` : '';

  // Players can request a magic item effect on their own character; needs DM approval
  const itemEffectRequestButton = (char.userId === currentUser.uid && !char.isDead) ?
    `<button onclick="requestItemEffect('${char.id}')" class="btn-info">🔮 Richiedi Oggetto Magico</button>` : '';

  // Players can request loot/rewards (gp + items); needs DM approval
  const lootButton = (char.userId === currentUser.uid && !char.isDead) ?
    `<button onclick="requestLoot('${char.id}')" class="btn-info">🎁 Richiedi Bottino</button>` : '';

  // DM can directly edit level
  const dmLevelButton = currentUserRole === 'dm' ?
    `<button onclick="dmEditLevel('${char.id}')" class="btn-warning">⬆️ Modifica Livello</button>` : '';

  // DM can grant an extra ASI or a feat directly (house rules / class exceptions)
  const dmGrantButtons = currentUserRole === 'dm' ?
    `<button onclick="dmGrantASI('${char.id}')" class="btn-warning">➕ ASI Extra</button>
     <button onclick="dmGrantFeat('${char.id}')" class="btn-warning">🏅 Assegna Talento</button>
     <button onclick="dmAddItemEffect('${char.id}')" class="btn-warning">🔮 Effetto Oggetto</button>` : '';
  
  const detailContent = document.getElementById('character-detail-content');
  detailContent.innerHTML = `
    <h3>${char.name}</h3>
    
    <div class="character-info">
      <p><strong>Classe:</strong> ${char.class}${char.subclass ? ` (${char.subclass})` : ''}</p>
      <p><strong>Livello:</strong> ${char.level || 1}</p>
      <p><strong>Razza:</strong> ${char.race}</p>
      ${char.background ? `<p><strong>Background:</strong> ${char.background}</p>` : ''}
      
      <h4 style="margin-top: 1.5rem;">Caratteristiche</h4>
      <div class="character-stats-grid">
        ${['str', 'dex', 'con', 'int', 'wis', 'cha'].map(k => {
          const val = stats[k] || 10;
          const modded = val !== (baseStats[k] || 10);
          return `
            <div class="stat-box">
              <span class="stat-label">${STAT_LABELS[k]}</span>
              <span class="stat-value">${val}${modded ? ' <span title="Modificato da oggetto magico" style="color: var(--success);">🔮</span>' : ''}</span>
              <span class="stat-modifier">${formatModifier(calculateModifier(val))}</span>
            </div>
          `;
        }).join('')}
      </div>

      ${(char.itemEffects && char.itemEffects.length > 0) ? `
        <h4 style="margin-top: 1.5rem;">Oggetti Magici</h4>
        ${char.itemEffects.map((e, i) => `
          <p>🔮 <strong>${e.name}</strong>: ${STAT_LABELS[e.ability] || e.ability} ${e.mode === 'set' ? `= ${e.value}` : `${e.value >= 0 ? '+' : ''}${e.value}`}
            ${currentUserRole === 'dm' ? `<button onclick="dmRemoveItemEffect('${char.id}', ${i})" class="btn-danger" style="padding: 0.1rem 0.5rem; margin-left: 0.5rem;">✕</button>` : ''}
          </p>
        `).join('')}
      ` : ''}

      <h4 style="margin-top: 1.5rem;">Combattimento</h4>
      <p><strong>Classe Armatura:</strong> ${combat.ac || 10}</p>
      <p><strong>Iniziativa:</strong> ${formatModifier(combat.initiative || 0)}</p>
      <p><strong>Velocità:</strong> ${combat.speed || 30} ft</p>
      <p><strong>Punti Ferita Max:</strong> ${effectiveHpMax(char)}${effectiveHpMax(char) !== (combat.hpMax || 0) ? ' <span title="Modificato da oggetto magico (COS)" style="color: var(--success);">🔮</span>' : ''}</p>
      <p><strong>PF Attuali:</strong> ${combat.hpCurrent !== undefined ? combat.hpCurrent : (combat.hpMax || 10)}${combat.hpTemp ? ` <span style="color: var(--success);">(+${combat.hpTemp} temp)</span>` : ''}</p>
      <p><strong>Bonus Competenza:</strong> +${char.proficiencyBonus || calculateProficiencyBonus(char.level || 1)}</p>
      ${char.hitDie ? `<p><strong>Dado Vita:</strong> d${char.hitDie}</p>` : ''}

      ${char.feats && char.feats.length > 0 ? `
        <h4 style="margin-top: 1.5rem;">Talenti</h4>
        ${char.feats.map(f => `
          <p><strong>${f.name}</strong>${f.level ? ` <span style="color: var(--gray);">(liv. ${f.level})</span>` : ''}</p>
          ${f.description ? `<p style="white-space: pre-wrap; margin-left: 1rem;">${f.description}</p>` : ''}
        `).join('')}
      ` : ''}

      <h4 style="margin-top: 1.5rem;">Borsa</h4>
      <p><strong>Oro:</strong> 🪙 ${char.gp || 0} Oro
        ${currentUserRole === 'dm' ? `<button onclick="dmGrantGold('${char.id}')" class="btn-warning" style="padding: 0.1rem 0.5rem; margin-left: 0.5rem;">± Oro</button>` : ''}
      </p>
      ${(char.items && char.items.length > 0)
        ? char.items.map(it => `<p>• ${it.qty}× ${it.name}${it.value ? ` <span style="color: var(--gray);">(${it.value} Oro)</span>` : ''}${it.details ? `<br><span style="color: var(--gray); font-size: 0.9rem; margin-left: 1rem;">${it.details}</span>` : ''}</p>`).join('')
        : '<p style="color: var(--gray);">Inventario oggetti vuoto.</p>'}

      ${char.weapons && char.weapons.length > 0 ? `
        <h4 style="margin-top: 1.5rem;">Armi</h4>
        ${char.weapons.map(weapon => {
          const profBonus = char.proficiencyBonus || calculateProficiencyBonus(char.level || 1);
          const attackBonus = calculateWeaponAttackBonus(weapon.attackStat, weapon.weaponBonus, profBonus, stats);
          return `
            <p><strong>${weapon.name}:</strong> 
            Attacco +${attackBonus}, 
            Danni ${weapon.damage}</p>
          `;
        }).join('')}
      ` : ''}
      
      <h4 style="margin-top: 1.5rem;">Tiri Salvezza</h4>
      <div class="saving-throws-display">
        ${['str', 'dex', 'con', 'int', 'wis', 'cha'].map(ability => {
          const profBonus = char.proficiencyBonus || calculateProficiencyBonus(char.level || 1);
          const isProficient = char.savingThrows?.[ability] || false;
          const bonus = calculateSavingThrow(stats, ability, isProficient, profBonus);
          const profIndicator = isProficient ? '<span class="save-proficient">★</span>' : '';
          
          return `
            <div class="save-item">
              <span class="save-name">${SAVE_NAMES[ability]}${profIndicator}</span>
              <span class="save-bonus">${formatModifier(bonus)}</span>
            </div>
          `;
        }).join('')}
      </div>
      
      <h4 style="margin-top: 1.5rem;">Abilità (Skills)</h4>
      <div class="skills-display">
        ${Object.keys(SKILLS_MAP).map(skill => {
          const skillData = (char.skills && char.skills[skill]) || { proficient: false, expertise: false };
          const profBonus = char.proficiencyBonus || calculateProficiencyBonus(char.level || 1);
          const bonus = calculateSkillBonus(stats, skill, skillData.proficient, skillData.expertise, profBonus);
          const profIndicator = skillData.expertise ? '<span class="skill-expertise">★★</span>' : 
                                skillData.proficient ? '<span class="skill-proficient">★</span>' : '';
          
          return `
            <div class="skill-item">
              <span class="skill-name">${SKILLS_ITALIAN[skill]}${profIndicator}</span>
              <span class="skill-bonus">${formatModifier(bonus)}</span>
            </div>
          `;
        }).join('')}
      </div>
      
      ${char.classFeatures ? `
        <h4 style="margin-top: 1.5rem;">Abilità di Classe</h4>
        <p style="white-space: pre-wrap;">${char.classFeatures}</p>
      ` : ''}
      
      ${char.subclassFeatures ? `
        <h4 style="margin-top: 1.5rem;">Abilità di Sottoclasse</h4>
        <p style="white-space: pre-wrap;">${char.subclassFeatures}</p>
      ` : ''}
      
      ${char.inventory ? `
        <h4 style="margin-top: 1.5rem;">Inventario</h4>
        <p style="white-space: pre-wrap;">${char.inventory}</p>
      ` : ''}
      
      ${char.spellcasting && char.spellcasting.ability ? `
        <h4 style="margin-top: 1.5rem;">Incantesimi</h4>
        ${(() => {
          const abilityNames = { int: 'Intelligenza', wis: 'Saggezza', cha: 'Carisma' };
          const abilityName = abilityNames[char.spellcasting.ability] || char.spellcasting.ability;
          const profBonus = char.proficiencyBonus || calculateProficiencyBonus(char.level || 1);
          const spellDC = calculateSpellDC(char.spellcasting.ability, profBonus, stats);
          const spellAttack = calculateSpellAttackBonus(char.spellcasting.ability, profBonus, stats);
          return `
            <p><strong>Caratteristica Incantatore:</strong> ${abilityName}</p>
            <p><strong>CD Tiro Salvezza:</strong> ${spellDC}</p>
            <p><strong>Bonus Attacco Incantesimi:</strong> +${spellAttack}</p>
          `;
        })()}
        ${char.spellcasting.cantrips ? `
          <p style="margin-top: 0.5rem;"><strong>Trucchetti:</strong></p>
          <p style="white-space: pre-wrap; margin-left: 1rem;">${char.spellcasting.cantrips}</p>
        ` : ''}
        ${[1,2,3,4,5,6,7,8,9].map(level => {
          const levelData = char.spellcasting[`level${level}`];
          if (levelData && levelData.spells) {
            return `
              <p style="margin-top: 0.5rem;"><strong>Livello ${level} (Slot: ${levelData.slots || 0}):</strong></p>
              <p style="white-space: pre-wrap; margin-left: 1rem;">${levelData.spells}</p>
            `;
          }
          return '';
        }).join('')}
      ` : char.spells ? `
        <h4 style="margin-top: 1.5rem;">Incantesimi</h4>
        ${char.spellAbility ? `<p><strong>Caratteristica:</strong> ${char.spellAbility}</p>` : ''}
        <p style="white-space: pre-wrap;">${char.spells}</p>
      ` : ''}
      
      ${char.notes ? `
        <h4 style="margin-top: 1.5rem;">Note</h4>
        <p style="white-space: pre-wrap;">${char.notes}</p>
      ` : ''}
    </div>
    
    <div class="character-actions">
      ${editButton}
      ${levelUpButton}
      ${itemEffectRequestButton}
      ${lootButton}
      ${dmLevelButton}
      ${dmGrantButtons}
      ${killButton}
      <button onclick="exportToPDF('${char.id}')" class="btn-info">📄 Esporta PDF</button>
      <button onclick="hideCharacterDetail()" class="btn-secondary">Chiudi</button>
    </div>
  `;
  
  showElement('character-detail');
};

window.hideCharacterDetail = function() {
  hideElement('character-detail');
};

window.showEditCharacter = function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  
  hideCharacterDetail();
  
  const stats = char.stats || {};
  const combat = char.combat || {};
  
  // Populate form
  document.getElementById('edit-char-id').value = char.id;
  document.getElementById('edit-char-name').value = char.name;
  document.getElementById('edit-char-class').value = char.class;
  document.getElementById('edit-char-subclass').value = char.subclass || '';
  document.getElementById('edit-char-race').value = char.race;
  document.getElementById('edit-char-background').value = char.background || '';
  document.getElementById('edit-char-level').value = char.level || 1;
  document.getElementById('edit-char-str').value = stats.str || 10;
  document.getElementById('edit-char-dex').value = stats.dex || 10;
  document.getElementById('edit-char-con').value = stats.con || 10;
  document.getElementById('edit-char-int').value = stats.int || 10;
  document.getElementById('edit-char-wis').value = stats.wis || 10;
  document.getElementById('edit-char-cha').value = stats.cha || 10;
  document.getElementById('edit-char-ac').value = combat.ac || 10;
  document.getElementById('edit-char-initiative').value = combat.initiative || 0;
  document.getElementById('edit-char-speed').value = combat.speed || 30;
  document.getElementById('edit-char-hp-max').value = combat.hpMax || 10;
  document.getElementById('edit-char-hp-current').value = combat.hpCurrent !== undefined ? combat.hpCurrent : (combat.hpMax || 10);
  document.getElementById('edit-char-hp-temp').value = combat.hpTemp || 0;
  document.getElementById('edit-char-class-features').value = char.classFeatures || '';
  document.getElementById('edit-char-subclass-features').value = char.subclassFeatures || '';
  document.getElementById('edit-char-inventory').value = char.inventory || '';
  
  // Populate weapons (TODO: add weapons UI to edit form)
  // For now, weapons can only be added during character creation
  
  // Populate spellcasting
  const spellcasting = char.spellcasting || {};
  document.getElementById('edit-char-spell-ability').value = spellcasting.ability || '';
  document.getElementById('edit-char-cantrips').value = spellcasting.cantrips || '';
  
  for (let i = 1; i <= 9; i++) {
    const levelData = spellcasting[`level${i}`];
    const spellListElem = document.querySelector(`.edit-spell-list[data-level="${i}"]`);
    const spellSlotsElem = document.querySelector(`.edit-spell-slots[data-level="${i}"]`);
    if (spellListElem && spellSlotsElem) {
      spellListElem.value = (levelData && levelData.spells) || '';
      spellSlotsElem.value = (levelData && levelData.slots) || '';
    }
  }
  
  document.getElementById('edit-char-notes').value = char.notes || '';
  
  // Populate skills
  if (char.skills) {
    populateSkillsForm(char.skills, 'edit-skill');
  }
  
  // Populate saving throws
  if (char.savingThrows) {
    populateSavingThrowsForm(char.savingThrows, 'edit-save');
  }
  
  showElement('edit-character-form');
};

window.hideEditCharacter = function() {
  hideElement('edit-character-form');
};

window.handleEditCharacter = async function(event) {
  event.preventDefault();
  
  if (!currentUser) return;
  
  const charId = document.getElementById('edit-char-id').value;
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  
  const newData = {
    name: document.getElementById('edit-char-name').value.trim(),
    class: document.getElementById('edit-char-class').value.trim(),
    subclass: document.getElementById('edit-char-subclass').value.trim(),
    race: document.getElementById('edit-char-race').value.trim(),
    background: document.getElementById('edit-char-background').value.trim(),
    stats: {
      str: parseInt(document.getElementById('edit-char-str').value),
      dex: parseInt(document.getElementById('edit-char-dex').value),
      con: parseInt(document.getElementById('edit-char-con').value),
      int: parseInt(document.getElementById('edit-char-int').value),
      wis: parseInt(document.getElementById('edit-char-wis').value),
      cha: parseInt(document.getElementById('edit-char-cha').value)
    },
    combat: {
      ac: parseInt(document.getElementById('edit-char-ac').value),
      initiative: parseInt(document.getElementById('edit-char-initiative').value),
      speed: parseInt(document.getElementById('edit-char-speed').value),
      hpMax: parseInt(document.getElementById('edit-char-hp-max').value),
      hpCurrent: parseInt(document.getElementById('edit-char-hp-current').value),
      hpTemp: parseInt(document.getElementById('edit-char-hp-temp').value) || 0
    },
    savingThrows: readSavingThrowsFromForm('edit-save'),
    skills: readSkillsFromForm('edit-skill'),
    classFeatures: document.getElementById('edit-char-class-features').value.trim(),
    subclassFeatures: document.getElementById('edit-char-subclass-features').value.trim(),
    inventory: document.getElementById('edit-char-inventory').value.trim(),
    notes: document.getElementById('edit-char-notes').value.trim()
  };
  
  // Raccogli spellcasting modificato
  const spellcasting = {
    ability: document.getElementById('edit-char-spell-ability').value,
    cantrips: document.getElementById('edit-char-cantrips').value.trim()
  };
  
  for (let i = 1; i <= 9; i++) {
    const spellListElem = document.querySelector(`.edit-spell-list[data-level="${i}"]`);
    const spellSlotsElem = document.querySelector(`.edit-spell-slots[data-level="${i}"]`);
    if (spellListElem && spellSlotsElem) {
      const spells = spellListElem.value.trim();
      const slots = parseInt(spellSlotsElem.value) || 0;
      if (spells || slots) {
        spellcasting[`level${i}`] = { spells, slots };
      }
    }
  }
  
  newData.spellcasting = spellcasting;
  // Keep weapons from original character (can't edit weapons in edit form yet)
  newData.weapons = char.weapons || [];
  
  try {
    showLoading();
    
    // Create pending change
    await addDoc(collection(db, 'pending_changes'), {
      type: 'character_edit',
      characterId: charId,
      characterName: char.name,
      requestedBy: currentUser.uid,
      requestedByName: currentUsername,
      oldData: {
        name: char.name,
        class: char.class,
        subclass: char.subclass,
        race: char.race,
        background: char.background,
        stats: char.stats,
        combat: char.combat,
        classFeatures: char.classFeatures,
        subclassFeatures: char.subclassFeatures,
        inventory: char.inventory,
        weapons: char.weapons,
        spellcasting: char.spellcasting,
        notes: char.notes
      },
      newData: newData,
      status: 'pending',
      createdAt: serverTimestamp()
    });
    
    hideLoading();
    hideEditCharacter();
    alert('Modifiche inviate per approvazione! Il DM le esaminerà presto.');
    
  } catch (error) {
    hideLoading();
    console.error('Errore invio modifiche:', error);
    alert('Errore nell\'invio delle modifiche');
  }
};

window.dmEditLevel = async function(charId) {
  if (currentUserRole !== 'dm') {
    alert('Solo i DM possono modificare direttamente il livello!');
    return;
  }
  
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  
  const newLevel = prompt(`Inserisci il nuovo livello per ${char.name}:`, char.level || 1);
  if (!newLevel || isNaN(newLevel)) return;
  
  const level = parseInt(newLevel);
  if (level < 1 || level > 20) {
    alert('Il livello deve essere tra 1 e 20!');
    return;
  }
  
  try {
    showLoading();
    const charRef = doc(db, 'characters', charId);
    const profBonus = calculateProficiencyBonus(level);
    await updateDoc(charRef, { 
      level: level,
      proficiencyBonus: profBonus
    });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert(`Livello di ${char.name} aggiornato a ${level}!\nBonus Competenza: +${profBonus}`);
  } catch (error) {
    hideLoading();
    console.error('Errore aggiornamento livello:', error);
    alert('Errore nell\'aggiornamento del livello');
  }
};

// === RICHIESTA PASSAGGIO DI LIVELLO (PLAYER) ===
window.showLevelUpRequest = function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  if (char.userId !== currentUser.uid) {
    alert('Puoi richiedere il passaggio di livello solo per i tuoi personaggi!');
    return;
  }

  const currentLevel = char.level || 1;
  const newLevel = currentLevel + 1;
  if (newLevel > 20) {
    alert('Livello massimo (20) già raggiunto!');
    return;
  }

  const stats = char.stats || {};
  const conMod = calculateModifier(stats.con || 10);
  const currentHitDie = char.hitDie || 8;
  levelUpCtx = { conMod };

  const abilityOptions = (selected) => [
    ['str', 'FOR'], ['dex', 'DES'], ['con', 'COS'],
    ['int', 'INT'], ['wis', 'SAG'], ['cha', 'CAR']
  ].map(([k, l]) => `<option value="${k}"${k === selected ? ' selected' : ''}>${l}</option>`).join('');

  const asiBlock = isASILevel(newLevel) ? `
    <div class="form-section">
      <h4>Miglioramento del livello ${newLevel} (ASI)</h4>
      <p style="color: var(--gray);">A questo livello puoi aumentare le caratteristiche oppure prendere un talento.</p>
      <label><input type="radio" name="asi-mode" value="plus2" checked onchange="onAsiModeChange()"> +2 a una caratteristica</label><br>
      <label><input type="radio" name="asi-mode" value="plus1" onchange="onAsiModeChange()"> +1 a due caratteristiche</label><br>
      <label><input type="radio" name="asi-mode" value="feat" onchange="onAsiModeChange()"> Talento</label>

      <div id="asi-plus2" style="margin-top: 0.75rem;">
        <label>Caratteristica (+2)</label>
        <select id="asi-ability-a">${abilityOptions('con')}</select>
      </div>
      <div id="asi-plus1" style="margin-top: 0.75rem; display:none;">
        <label>Prima caratteristica (+1)</label>
        <select id="asi-ability-b">${abilityOptions('con')}</select>
        <label>Seconda caratteristica (+1)</label>
        <select id="asi-ability-c">${abilityOptions('dex')}</select>
      </div>
      <div id="asi-feat" style="margin-top: 0.75rem; display:none;">
        <label>Nome talento</label>
        <input type="text" id="asi-feat-name" placeholder="Es. Gladiatore, Esperto..." />
        <label>Descrizione (opzionale)</label>
        <textarea id="asi-feat-desc" rows="3" placeholder="Effetti del talento..."></textarea>
      </div>
    </div>
  ` : '';

  document.getElementById('levelup-content').innerHTML = `
    <h3>⬆️ Richiesta Passaggio di Livello</h3>
    <p><strong>${char.name}</strong>: livello ${currentLevel} → <strong>${newLevel}</strong></p>

    <div class="form-section">
      <h4>Punti Ferita</h4>
      <label>Dado Vita</label>
      <select id="levelup-hitdie" onchange="updateHpPreview()">
        ${HIT_DICE.map(d => `<option value="${d}"${d === currentHitDie ? ' selected' : ''}>d${d}</option>`).join('')}
      </select>
      <p style="margin-top: 0.5rem;">PF guadagnati (media + COS): <strong id="hp-gain-preview"></strong></p>
    </div>

    ${asiBlock}

    <div class="modal-buttons">
      <button type="button" class="btn-success" onclick="submitLevelUpRequest('${char.id}')">Invia Richiesta</button>
      <button type="button" onclick="hideLevelUpRequest()">Annulla</button>
    </div>
  `;

  showElement('levelup-modal');
  updateHpPreview();
};

window.hideLevelUpRequest = function() {
  hideElement('levelup-modal');
  levelUpCtx = null;
};

window.onAsiModeChange = function() {
  const mode = document.querySelector('input[name="asi-mode"]:checked')?.value;
  document.getElementById('asi-plus2').style.display = mode === 'plus2' ? 'block' : 'none';
  document.getElementById('asi-plus1').style.display = mode === 'plus1' ? 'block' : 'none';
  document.getElementById('asi-feat').style.display = mode === 'feat' ? 'block' : 'none';
};

window.updateHpPreview = function() {
  if (!levelUpCtx) return;
  const hitDie = parseInt(document.getElementById('levelup-hitdie').value) || 8;
  const gain = hpGainForLevel(hitDie, levelUpCtx.conMod);
  document.getElementById('hp-gain-preview').textContent = `+${gain} PF`;
};

window.submitLevelUpRequest = async function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;

  const currentLevel = char.level || 1;
  const newLevel = currentLevel + 1;
  const hitDie = parseInt(document.getElementById('levelup-hitdie').value) || 8;

  // Cloni completi: updateDoc sostituisce interamente le mappe annidate
  const stats = Object.assign({ str: 10, dex: 10, con: 10, int: 10, wis: 10, cha: 10 }, char.stats || {});
  const combat = Object.assign({ ac: 10, initiative: 0, speed: 30, hpMax: 10, hpCurrent: 10, hpTemp: 0 }, char.combat || {});
  const feats = Array.isArray(char.feats) ? char.feats.slice() : [];

  // Mod COS PRIMA dell'ASI (per calcolare l'eventuale aumento retroattivo dei PF)
  const oldConMod = calculateModifier(stats.con);

  let summary = `Livello ${currentLevel} → ${newLevel}.`;

  // Applica l'ASI ai punteggi BASE prima di calcolare i PF
  if (isASILevel(newLevel)) {
    const mode = document.querySelector('input[name="asi-mode"]:checked')?.value;
    if (mode === 'plus2') {
      const a = document.getElementById('asi-ability-a').value;
      stats[a] = Math.min(20, (stats[a] || 10) + 2);
      summary += ` ASI: +2 ${STAT_LABELS[a]}.`;
    } else if (mode === 'plus1') {
      const b = document.getElementById('asi-ability-b').value;
      const c = document.getElementById('asi-ability-c').value;
      if (b === c) { alert('Scegli due caratteristiche diverse per il +1/+1!'); return; }
      stats[b] = Math.min(20, (stats[b] || 10) + 1);
      stats[c] = Math.min(20, (stats[c] || 10) + 1);
      summary += ` ASI: +1 ${STAT_LABELS[b]}, +1 ${STAT_LABELS[c]}.`;
    } else if (mode === 'feat') {
      const name = document.getElementById('asi-feat-name').value.trim();
      if (!name) { alert('Inserisci il nome del talento!'); return; }
      const desc = document.getElementById('asi-feat-desc').value.trim();
      feats.push({ name, description: desc, level: newLevel, source: 'ASI' });
      summary += ` Talento: ${name}.`;
    }
  }

  // PF: il nuovo livello usa il mod COS aggiornato; se il mod COS è salito,
  // aumento retroattivo di +1 PF per ogni livello precedente.
  const newConMod = calculateModifier(stats.con);
  const deltaConMod = newConMod - oldConMod;
  const hpGain = hpGainForLevel(hitDie, newConMod);
  const retroHp = Math.max(0, deltaConMod) * (newLevel - 1);
  const totalHp = hpGain + retroHp;

  const prevHpCurrent = combat.hpCurrent !== undefined ? combat.hpCurrent : combat.hpMax;
  combat.hpMax = (combat.hpMax || 0) + totalHp;
  combat.hpCurrent = prevHpCurrent + totalHp;

  summary += ` +${hpGain} PF (d${hitDie})`;
  if (retroHp > 0) summary += `, +${retroHp} PF retroattivi (COS +${deltaConMod})`;
  summary += '.';

  const newData = {
    level: newLevel,
    proficiencyBonus: calculateProficiencyBonus(newLevel),
    hitDie: hitDie,
    stats: stats,
    combat: combat,
    feats: feats
  };

  try {
    showLoading();
    await addDoc(collection(db, 'pending_changes'), {
      type: 'level_up',
      characterId: charId,
      characterName: char.name,
      requestedBy: currentUser.uid,
      requestedByName: currentUsername,
      summary: summary,
      oldData: {
        level: currentLevel,
        hitDie: char.hitDie || null,
        stats: char.stats || {},
        combat: char.combat || {},
        feats: char.feats || []
      },
      newData: newData,
      status: 'pending',
      createdAt: serverTimestamp()
    });
    hideLoading();
    hideLevelUpRequest();
    hideCharacterDetail();
    alert('Richiesta di passaggio di livello inviata! Il DM la esaminerà presto.');
  } catch (error) {
    hideLoading();
    console.error('Errore invio richiesta livello:', error);
    alert('Errore nell\'invio della richiesta');
  }
};

// === STRUMENTI DM: ASI EXTRA E TALENTI DIRETTI ===
window.dmGrantASI = async function(charId) {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono concedere un ASI extra!'); return; }
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;

  const ability = prompt('Quale caratteristica aumentare? (str/dex/con/int/wis/cha)');
  if (!ability) return;
  const key = ability.trim().toLowerCase();
  if (!['str', 'dex', 'con', 'int', 'wis', 'cha'].includes(key)) {
    alert('Caratteristica non valida! Usa: str, dex, con, int, wis, cha');
    return;
  }
  const amount = parseInt(prompt('Di quanto aumentarla? (es. 1 o 2)', '2'));
  if (isNaN(amount) || amount === 0) return;

  const stats = Object.assign({}, char.stats || {});
  stats[key] = Math.min(30, Math.max(1, (stats[key] || 10) + amount));

  try {
    showLoading();
    await updateDoc(doc(db, 'characters', charId), { stats });
    await logAudit({ charId, charName: char.name, action: 'dm-asi', message: `ASI extra dal DM: ${STAT_LABELS[key]} ${amount >= 0 ? '+' : ''}${amount}` });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert('Caratteristica aggiornata!');
  } catch (error) {
    hideLoading();
    console.error('Errore ASI extra:', error);
    alert('Errore nell\'aggiornamento');
  }
};

window.dmGrantFeat = async function(charId) {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono assegnare talenti!'); return; }
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;

  const name = prompt('Nome del talento:');
  if (!name || !name.trim()) return;
  const description = (prompt('Descrizione (opzionale):') || '').trim();

  const feats = Array.isArray(char.feats) ? char.feats.slice() : [];
  feats.push({ name: name.trim(), description, level: char.level || null, source: 'DM' });

  try {
    showLoading();
    await updateDoc(doc(db, 'characters', charId), { feats });
    await logAudit({ charId, charName: char.name, action: 'dm-feat', message: `Talento assegnato dal DM: ${name.trim()}` });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert('Talento assegnato!');
  } catch (error) {
    hideLoading();
    console.error('Errore assegnazione talento:', error);
    alert('Errore nell\'assegnazione');
  }
};

// === RICHIESTA OGGETTO MAGICO (PLAYER → APPROVAZIONE DM) ===
function promptItemEffect() {
  const name = prompt('Nome oggetto magico (es. Cintura di Forza del Gigante):');
  if (!name || !name.trim()) return null;

  const ability = (prompt('Su quale caratteristica? (str/dex/con/int/wis/cha)') || '').trim().toLowerCase();
  if (!(ability in STAT_LABELS)) {
    alert('Caratteristica non valida! Usa: str, dex, con, int, wis, cha');
    return null;
  }

  const modeInput = (prompt('Tipo effetto: "set" (imposta il punteggio a) oppure "bonus" (aggiunge)?', 'set') || '').trim().toLowerCase();
  const mode = modeInput === 'bonus' ? 'bonus' : 'set';

  const value = parseInt(prompt(mode === 'set' ? 'Imposta il punteggio a: (es. 19)' : 'Bonus da aggiungere: (es. 2 o -2)', mode === 'set' ? '19' : '2'));
  if (isNaN(value)) return null;

  return { name: name.trim(), ability, mode, value };
}

function describeItemEffect(e) {
  return `${e.name} (${STAT_LABELS[e.ability] || e.ability} ${e.mode === 'set' ? `= ${e.value}` : `${e.value >= 0 ? '+' : ''}${e.value}`})`;
}

window.requestItemEffect = async function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  if (char.userId !== currentUser.uid) {
    alert('Puoi richiedere oggetti magici solo per i tuoi personaggi!');
    return;
  }

  const effect = promptItemEffect();
  if (!effect) return;

  const existing = Array.isArray(char.itemEffects) ? char.itemEffects.slice() : [];
  const itemEffects = existing.concat([effect]);

  try {
    showLoading();
    await addDoc(collection(db, 'pending_changes'), {
      type: 'item_effect',
      characterId: charId,
      characterName: char.name,
      requestedBy: currentUser.uid,
      requestedByName: currentUsername,
      summary: `Oggetto magico: ${describeItemEffect(effect)}`,
      oldData: { itemEffects: existing },
      newData: { itemEffects: itemEffects },
      status: 'pending',
      createdAt: serverTimestamp()
    });
    hideLoading();
    hideCharacterDetail();
    alert('Richiesta oggetto magico inviata! Il DM la esaminerà presto.');
  } catch (error) {
    hideLoading();
    console.error('Errore richiesta oggetto:', error);
    alert('Errore nell\'invio della richiesta');
  }
};

// === RICHIESTA BOTTINO / RICOMPENSA (PLAYER → APPROVAZIONE DM) ===
window.requestLoot = async function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  if (char.userId !== currentUser.uid) {
    alert('Puoi richiedere bottino solo per i tuoi personaggi!');
    return;
  }

  const gp = parseInt(prompt('Oro guadagnato (gp):', '0')) || 0;
  const items = [];
  while (true) {
    const name = prompt('Nome oggetto da aggiungere (lascia vuoto per finire):', '');
    if (!name || !name.trim()) break;
    const qty = parseInt(prompt(`Quantità di ${name.trim()}:`, '1')) || 1;
    const value = parseInt(prompt(`Valore unitario in gp di ${name.trim()} (0 se ignoto):`, '0')) || 0;
    items.push({ name: name.trim(), qty, value, category: 'Bottino' });
  }

  if (gp <= 0 && items.length === 0) { alert('Niente da richiedere.'); return; }

  const parts = [];
  if (gp > 0) parts.push(`${gp} gp`);
  items.forEach(it => parts.push(`${it.qty}× ${it.name}`));

  try {
    showLoading();
    await addDoc(collection(db, 'pending_changes'), {
      type: 'loot',
      characterId: charId,
      characterName: char.name,
      requestedBy: currentUser.uid,
      requestedByName: currentUsername,
      summary: `Bottino: ${parts.join(', ')}`,
      grantGp: gp,
      grantItems: items,
      status: 'pending',
      createdAt: serverTimestamp()
    });
    hideLoading();
    hideCharacterDetail();
    alert('Richiesta bottino inviata! Il DM la esaminerà presto.');
  } catch (error) {
    hideLoading();
    console.error('Errore richiesta bottino:', error);
    alert('Errore nell\'invio della richiesta');
  }
};

// === STRUMENTI DM: EFFETTI DA OGGETTI MAGICI SULLE CARATTERISTICHE ===
window.dmAddItemEffect = async function(charId) {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono gestire gli oggetti magici!'); return; }
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;

  const effect = promptItemEffect();
  if (!effect) return;

  const itemEffects = Array.isArray(char.itemEffects) ? char.itemEffects.slice() : [];
  itemEffects.push(effect);

  try {
    showLoading();
    await updateDoc(doc(db, 'characters', charId), { itemEffects });
    await logAudit({ charId, charName: char.name, action: 'dm-item', message: `Oggetto magico dal DM: ${describeItemEffect(effect)}` });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert('Effetto oggetto magico applicato!');
  } catch (error) {
    hideLoading();
    console.error('Errore effetto oggetto:', error);
    alert('Errore nell\'applicazione');
  }
};

window.dmRemoveItemEffect = async function(charId, index) {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono rimuovere gli oggetti magici!'); return; }
  const char = currentCharacters.find(c => c.id === charId);
  if (!char || !Array.isArray(char.itemEffects)) return;

  const effect = char.itemEffects[index];
  if (!effect) return;
  if (!confirm(`Rimuovere l'effetto di "${effect.name}"?`)) return;

  const itemEffects = char.itemEffects.slice();
  itemEffects.splice(index, 1);

  try {
    showLoading();
    await updateDoc(doc(db, 'characters', charId), { itemEffects });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert('Effetto rimosso!');
  } catch (error) {
    hideLoading();
    console.error('Errore rimozione effetto:', error);
    alert('Errore nella rimozione');
  }
};

window.killCharacter = async function(charId) {
  if (currentUserRole !== 'dm') {
    alert('Solo i Dungeon Master possono dichiarare morti i personaggi!');
    return;
  }
  
  if (!confirm('Sei sicuro di voler dichiarare questo personaggio morto? Questa azione è irreversibile!')) {
    return;
  }
  
  try {
    showLoading();
    
    const charRef = doc(db, 'characters', charId);
    await updateDoc(charRef, {
      isDead: true,
      deathDate: serverTimestamp()
    });
    
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    
    alert('Il personaggio è ora nella Hall of Fallen Heroes 💀');
  } catch (error) {
    hideLoading();
    console.error('Errore:', error);
    alert('Errore nell\'aggiornamento del personaggio');
  }
};

// === PENDING CHANGES (DM) ===
async function loadPendingChanges() {
  if (currentUserRole !== 'dm') return;
  
  const listEl = document.getElementById('pending-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  
  try {
    const q = query(
      collection(db, 'pending_changes'),
      where('status', '==', 'pending')
    );
    
    const snapshot = await getDocs(q);
    pendingChanges = [];
    
    snapshot.forEach(doc => {
      pendingChanges.push({ id: doc.id, ...doc.data() });
    });
    
    pendingChanges.sort((a, b) => {
      if (!a.createdAt || !b.createdAt) return 0;
      return b.createdAt.seconds - a.createdAt.seconds;
    });
    
    renderPendingChanges();
    updatePendingCount();
  } catch (error) {
    console.error('Errore caricamento pending:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento</p></div>';
  }
}

function renderPendingChanges() {
  const listEl = document.getElementById('pending-list');
  
  if (pendingChanges.length === 0) {
    listEl.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">✅</div>
        <p>Nessuna modifica in attesa!</p>
        <p>Tutte le richieste sono state gestite.</p>
      </div>
    `;
    return;
  }
  
  listEl.innerHTML = pendingChanges.map(change => {
    const date = change.createdAt ? 
      new Date(change.createdAt.seconds * 1000).toLocaleDateString('it-IT') : 
      'Data sconosciuta';
    
    const typeLabel = change.type === 'level_up' ? '⬆️ Passaggio di Livello'
      : change.type === 'item_effect' ? '🔮 Oggetto Magico'
      : change.type === 'loot' ? '🎁 Bottino'
      : 'Modifica Personaggio';
    return `
      <div class="card pending-card" onclick="showPendingDetail('${change.id}')">
        <span class="change-type">${typeLabel}</span>
        <h3>${change.characterName}</h3>
        <p class="requester"><strong>Richiesta da:</strong> ${change.requestedByName}</p>
        ${change.summary ? `<p>${change.summary}</p>` : ''}
        <p><strong>Data:</strong> ${date}</p>
        <span class="card-status status-pending">⏳ In Attesa</span>
      </div>
    `;
  }).join('');
}

window.showPendingDetail = function(changeId) {
  const change = pendingChanges.find(c => c.id === changeId);
  if (!change) return;
  
  const old = change.oldData || {};
  const newData = change.newData || {};
  
  // Build diff HTML
  let diffHtml = '';
  
  const fields = [
    { key: 'name', label: 'Nome' },
    { key: 'class', label: 'Classe' },
    { key: 'subclass', label: 'Sottoclasse' },
    { key: 'race', label: 'Razza' },
    { key: 'background', label: 'Background' }
  ];
  
  fields.forEach(field => {
    if (old[field.key] !== newData[field.key]) {
      diffHtml += `
        <div class="change-diff">
          <strong>${field.label}:</strong><br>
          <span class="old-value">- ${old[field.key] || '(vuoto)'}</span><br>
          <span class="new-value">+ ${newData[field.key] || '(vuoto)'}</span>
        </div>
      `;
    }
  });
  
  // Level / Dado Vita (soprattutto per le richieste di passaggio di livello)
  if (old.level !== undefined && old.level !== newData.level && newData.level !== undefined) {
    diffHtml += `
      <div class="change-diff">
        <strong>Livello:</strong>
        <span class="old-value">${old.level}</span> → <span class="new-value">${newData.level}</span>
      </div>
    `;
  }
  if (old.hitDie !== newData.hitDie && newData.hitDie !== undefined) {
    diffHtml += `
      <div class="change-diff">
        <strong>Dado Vita:</strong>
        <span class="old-value">${old.hitDie ? 'd' + old.hitDie : '(non impostato)'}</span> → <span class="new-value">d${newData.hitDie}</span>
      </div>
    `;
  }

  // Talenti aggiunti
  if (newData.feats) {
    const oldCount = (old.feats || []).length;
    const added = newData.feats.slice(oldCount);
    if (added.length > 0) {
      diffHtml += `
        <div class="change-diff">
          <strong>Talenti aggiunti:</strong><br>
          ${added.map(f => `<span class="new-value">+ ${f.name}${f.description ? ` — ${f.description}` : ''}</span>`).join('<br>')}
        </div>
      `;
    }
  }

  // Oggetti magici aggiunti (effetti sulle caratteristiche)
  if (newData.itemEffects) {
    const oldCount = (old.itemEffects || []).length;
    const added = newData.itemEffects.slice(oldCount);
    if (added.length > 0) {
      diffHtml += `
        <div class="change-diff">
          <strong>Oggetti magici aggiunti:</strong><br>
          ${added.map(e => `<span class="new-value">+ ${describeItemEffect(e)}</span>`).join('<br>')}
        </div>
      `;
    }
  }

  // Bottino / ricompensa
  if (change.type === 'loot') {
    const parts = [];
    if (change.grantGp) parts.push(`${change.grantGp} gp`);
    (change.grantItems || []).forEach(it => parts.push(`${it.qty}× ${it.name}${it.value ? ` (${it.value} gp)` : ''}`));
    if (parts.length) {
      diffHtml += `
        <div class="change-diff">
          <strong>Bottino da aggiungere:</strong><br>
          ${parts.map(p => `<span class="new-value">+ ${p}</span>`).join('<br>')}
        </div>
      `;
    }
  }

  // Stats comparison
  if (old.stats && newData.stats) {
    const statNames = { str: 'FOR', dex: 'DES', con: 'COS', int: 'INT', wis: 'SAG', cha: 'CAR' };
    let statsChanged = false;
    let statsDiff = '<div class="change-diff"><strong>Caratteristiche:</strong><br>';
    
    for (const [key, label] of Object.entries(statNames)) {
      if (old.stats[key] !== newData.stats[key]) {
        statsChanged = true;
        statsDiff += `${label}: <span class="old-value">${old.stats[key]}</span> → <span class="new-value">${newData.stats[key]}</span><br>`;
      }
    }
    
    statsDiff += '</div>';
    if (statsChanged) diffHtml += statsDiff;
  }
  
  // Combat comparison
  if (old.combat && newData.combat) {
    const combatNames = { ac: 'CA', initiative: 'Iniziativa', speed: 'Velocità', hpMax: 'PF Max' };
    let combatChanged = false;
    let combatDiff = '<div class="change-diff"><strong>Combattimento:</strong><br>';
    
    for (const [key, label] of Object.entries(combatNames)) {
      if (old.combat[key] !== newData.combat[key]) {
        combatChanged = true;
        combatDiff += `${label}: <span class="old-value">${old.combat[key]}</span> → <span class="new-value">${newData.combat[key]}</span><br>`;
      }
    }
    
    combatDiff += '</div>';
    if (combatChanged) diffHtml += combatDiff;
  }
  
  // Text fields
  const textFields = [
    { key: 'classFeatures', label: 'Abilità di Classe' },
    { key: 'subclassFeatures', label: 'Abilità di Sottoclasse' },
    { key: 'inventory', label: 'Inventario' },
    { key: 'spellAbility', label: 'Caratteristica Incantesimi' },
    { key: 'spells', label: 'Incantesimi' },
    { key: 'notes', label: 'Note' }
  ];
  
  textFields.forEach(field => {
    if (old[field.key] !== newData[field.key]) {
      diffHtml += `
        <div class="change-diff">
          <strong>${field.label}:</strong><br>
          <span class="old-value">- ${(old[field.key] || '(vuoto)').substring(0, 100)}${(old[field.key] || '').length > 100 ? '...' : ''}</span><br>
          <span class="new-value">+ ${(newData[field.key] || '(vuoto)').substring(0, 100)}${(newData[field.key] || '').length > 100 ? '...' : ''}</span>
        </div>
      `;
    }
  });
  
  if (!diffHtml) {
    diffHtml = '<p style="color: var(--gray);">Nessuna modifica rilevata</p>';
  }
  
  const modalContent = `
    <h3>${change.type === 'level_up' ? '⬆️ Passaggio di Livello' : change.type === 'item_effect' ? '🔮 Oggetto Magico' : change.type === 'loot' ? '🎁 Bottino' : 'Richiesta Modifica'}: ${change.characterName}</h3>
    <p><strong>Richiesta da:</strong> ${change.requestedByName}</p>
    ${change.summary ? `<p><em>${change.summary}</em></p>` : ''}

    <h4 style="margin-top: 1.5rem;">Modifiche Proposte:</h4>
    ${diffHtml}
    
    <div class="approval-actions">
      <button onclick="approvePendingChange('${change.id}')" class="btn-success">✓ Approva</button>
      <button onclick="rejectPendingChange('${change.id}')" class="btn-danger">✗ Rifiuta</button>
      <button onclick="closePendingDetail()" class="btn-secondary">Chiudi</button>
    </div>
  `;
  
  document.getElementById('pending-detail-content').innerHTML = modalContent;
  showElement('pending-detail');
};

window.closePendingDetail = function() {
  hideElement('pending-detail');
};

window.approvePendingChange = async function(changeId) {
  const change = pendingChanges.find(c => c.id === changeId);
  if (!change) return;
  
  if (!confirm(`Approvare le modifiche a ${change.characterName}?`)) return;
  
  try {
    showLoading();

    // Update character
    const charRef = doc(db, 'characters', change.characterId);
    if (change.type === 'loot') {
      // Applica i delta sul valore aggiornato (evita di sovrascrivere cambi nel frattempo)
      const snap = await getDoc(charRef);
      const cur = snap.exists() ? snap.data() : {};
      let items = cur.items || [];
      (change.grantItems || []).forEach(it => { items = addItemToList(items, it); });
      await updateDoc(charRef, { gp: (cur.gp || 0) + (change.grantGp || 0), items });
    } else {
      await updateDoc(charRef, change.newData);
    }

    // Update pending change status
    const pendingRef = doc(db, 'pending_changes', changeId);
    await updateDoc(pendingRef, {
      status: 'approved',
      approvedBy: currentUser.uid,
      approvedAt: serverTimestamp()
    });

    const auditMsg = change.type === 'loot' ? 'Bottino approvato'
      : change.type === 'level_up' ? 'Passaggio di livello approvato'
      : change.type === 'item_effect' ? 'Oggetto magico approvato'
      : 'Modifica approvata';
    await logAudit({
      charId: change.characterId,
      charName: change.characterName,
      action: 'approve',
      message: `${auditMsg}${change.summary ? ': ' + change.summary : ''}`,
      gpDelta: change.type === 'loot' ? (change.grantGp || 0) : 0
    });

    hideLoading();
    closePendingDetail();
    loadPendingChanges();
    alert('Modifiche approvate con successo!');
    
  } catch (error) {
    hideLoading();
    console.error('Errore approvazione:', error);
    alert('Errore nell\'approvazione delle modifiche');
  }
};

window.rejectPendingChange = async function(changeId) {
  const change = pendingChanges.find(c => c.id === changeId);
  if (!change) return;
  
  if (!confirm(`Rifiutare le modifiche a ${change.characterName}?`)) return;
  
  try {
    showLoading();
    
    const pendingRef = doc(db, 'pending_changes', changeId);
    await updateDoc(pendingRef, {
      status: 'rejected',
      rejectedBy: currentUser.uid,
      rejectedAt: serverTimestamp()
    });
    
    hideLoading();
    closePendingDetail();
    loadPendingChanges();
    alert('Modifiche rifiutate.');
    
  } catch (error) {
    hideLoading();
    console.error('Errore rifiuto:', error);
    alert('Errore nel rifiuto delle modifiche');
  }
};

// === CAMPAGNE & QUEST ===
// Punto d'ingresso della sezione: elenco delle campagne.
async function loadQuests() {
  if (!currentUser) return;
  currentCampaignId = null;

  const listEl = document.getElementById('quests-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';

  try {
    const [campSnap, questSnap] = await Promise.all([
      getDocs(collection(db, 'campaigns')),
      getDocs(collection(db, 'quests'))
    ]);
    campaignsCache = [];
    campSnap.forEach(d => campaignsCache.push({ id: d.id, ...d.data() }));

    const activeByCampaign = {};
    questSnap.forEach(d => {
      const q = d.data();
      if (!q.isCompleted && !q.isClosed) {
        const key = q.campaignId || '_none';
        activeByCampaign[key] = (activeByCampaign[key] || 0) + 1;
      }
    });

    campaignsCache.sort((a, b) => ((b.createdAt && b.createdAt.seconds) || 0) - ((a.createdAt && a.createdAt.seconds) || 0));
    renderCampaigns(activeByCampaign);
  } catch (error) {
    console.error('Errore caricamento campagne:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento delle campagne</p></div>';
  }
}

function renderCampaigns(activeByCampaign) {
  const listEl = document.getElementById('quests-list');
  const headerAdd = document.querySelector('#quests .add-btn');
  if (headerAdd) headerAdd.style.display = 'none';

  const addBtn = currentUserRole === 'dm'
    ? `<div class="character-actions" style="margin-bottom:1rem;"><button class="btn-success btn-lg" onclick="showAddCampaign()">+ Nuova Campagna</button></div>`
    : '';

  if (campaignsCache.length === 0) {
    listEl.innerHTML = addBtn + `
      <div class="empty-state">
        <div class="empty-state-icon">📜</div>
        <p>Nessuna campagna disponibile.</p>
        ${currentUserRole === 'dm' ? '<p>Inizia creando la tua prima campagna!</p>' : '<p>Il Dungeon Master creerà presto delle campagne!</p>'}
      </div>`;
    return;
  }

  listEl.innerHTML = addBtn + `<div class="cards-grid">${campaignsCache.map(c => {
    const n = activeByCampaign[c.id] || 0;
    const ended = c.status === 'ended';
    return `
      <div class="card" style="cursor:pointer;" onclick="openCampaign('${c.id}')">
        <h3>📖 ${c.title}</h3>
        ${c.description ? `<p>${c.description.substring(0, 120)}${c.description.length > 120 ? '...' : ''}</p>` : ''}
        <p style="margin-top:0.5rem;"><strong>Quest attive:</strong> ${n}</p>
        <span class="card-status ${ended ? 'status-dead' : 'status-active'}">${ended ? 'Conclusa' : 'In corso'}</span>
      </div>`;
  }).join('')}</div>`;
}

window.showAddCampaign = async function() {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono creare campagne!'); return; }
  const title = (prompt('Titolo della campagna:') || '').trim();
  if (!title) return;
  const description = (prompt('Descrizione (opzionale):') || '').trim();
  try {
    showLoading();
    await addDoc(collection(db, 'campaigns'), {
      title, description, createdBy: currentUser.uid, status: 'active', createdAt: serverTimestamp()
    });
    await notifyPlayers(`📜 Nuova campagna: "${title}"`, 'campaign');
    hideLoading();
    loadQuests();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.openCampaign = async function(campaignId) {
  currentCampaignId = campaignId;
  const campaign = campaignsCache.find(c => c.id === campaignId) || { title: 'Campagna' };
  const listEl = document.getElementById('quests-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  try {
    const snapshot = await getDocs(collection(db, 'quests'));
    currentQuests = [];
    snapshot.forEach(d => {
      const q = { id: d.id, ...d.data() };
      if (q.campaignId === campaignId && !q.isCompleted && !q.isClosed) currentQuests.push(q);
    });
    currentQuests.sort((a, b) => ((b.createdAt && b.createdAt.seconds) || 0) - ((a.createdAt && a.createdAt.seconds) || 0));
    renderCampaignQuests(campaign);
  } catch (e) {
    console.error(e);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento delle quest</p></div>';
  }
};

function renderCampaignQuests(campaign) {
  const listEl = document.getElementById('quests-list');
  const dmControls = currentUserRole === 'dm'
    ? `<button class="btn-success btn-lg" onclick="showAddQuest()">+ Nuova Quest</button>
       ${campaign.status === 'ended' ? '' : `<button class="btn-warning" onclick="endCampaign('${currentCampaignId}')">Concludi Campagna</button>`}`
    : '';
  const header = `
    <div class="character-actions" style="margin-bottom:1rem;">
      <button class="btn-secondary" onclick="loadQuests()">← Tutte le Campagne</button>
      ${dmControls}
    </div>
    <h3 style="margin-bottom:0.5rem;">📖 ${campaign.title}</h3>
    ${campaign.description ? `<p style="color:var(--gray); margin-bottom:1rem;">${campaign.description}</p>` : ''}
  `;

  if (currentQuests.length === 0) {
    listEl.innerHTML = header + `<div class="empty-state"><div class="empty-state-icon">🗺️</div><p>Nessuna quest attiva in questa campagna.</p></div>`;
    return;
  }

  listEl.innerHTML = header + `<div class="cards-grid">${currentQuests.map(quest => {
    const participants = quest.participants || [];
    const maxParticipants = quest.maxParticipants || 4;
    return `
      <div class="card" onclick="showQuestDetail('${quest.id}')">
        <h3>${quest.title}</h3>
        ${quest.questGiver ? `<p><strong>Affidata da:</strong> ${quest.questGiver}</p>` : ''}
        <p>${quest.description.substring(0, 100)}${quest.description.length > 100 ? '...' : ''}</p>
        <span class="difficulty-badge difficulty-${quest.difficulty}">${quest.difficulty}</span>
        <p style="margin-top: 0.5rem;"><strong>Slot:</strong> ${participants.length}/${maxParticipants}</p>
        <span class="card-status status-active">Attiva</span>
      </div>`;
  }).join('')}</div>`;
}

// Torna alla vista corretta dopo un'azione su una quest
function refreshQuestView() {
  if (currentCampaignId) openCampaign(currentCampaignId);
  else loadQuests();
}

window.endCampaign = async function(campaignId) {
  if (currentUserRole !== 'dm') return;
  if (!confirm('Concludere la campagna? Le quest resteranno nel Libro Mastro.')) return;
  try {
    showLoading();
    await updateDoc(doc(db, 'campaigns', campaignId), { status: 'ended', endedAt: serverTimestamp() });
    hideLoading();
    loadQuests();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
}

window.showAddQuest = function() {
  if (currentUserRole !== 'dm') {
    alert('Solo i Dungeon Master possono creare quest!');
    return;
  }
  if (!currentCampaignId) {
    alert('Apri prima una campagna in cui inserire la quest.');
    return;
  }

  document.getElementById('quest-title').value = '';
  document.getElementById('quest-giver').value = '';
  document.getElementById('quest-description').value = '';
  document.getElementById('quest-setting').value = '';
  document.getElementById('quest-rewards').value = '';
  document.getElementById('quest-difficulty').value = '';
  document.getElementById('quest-max-participants').value = '4';
  showElement('add-quest-form');
};

window.hideAddQuest = function() {
  hideElement('add-quest-form');
};

window.handleAddQuest = async function(event) {
  event.preventDefault();
  
  if (!currentUser) return;
  
  const title = document.getElementById('quest-title').value.trim();
  const questGiver = document.getElementById('quest-giver').value.trim();
  const description = document.getElementById('quest-description').value.trim();
  const setting = document.getElementById('quest-setting').value.trim();
  const rewards = document.getElementById('quest-rewards').value.trim();
  const difficulty = document.getElementById('quest-difficulty').value;
  const maxParticipants = parseInt(document.getElementById('quest-max-participants').value);
  
  try {
    showLoading();
    
    await addDoc(collection(db, 'quests'), {
      createdBy: currentUser.uid,
      title,
      questGiver,
      description,
      setting,
      rewards,
      difficulty,
      maxParticipants,
      campaignId: currentCampaignId || null,
      campaignTitle: (campaignsCache.find(c => c.id === currentCampaignId) || {}).title || '',
      participants: [],
      isCompleted: false,
      isClosed: false,
      createdAt: serverTimestamp()
    });

    const campTitle = (campaignsCache.find(c => c.id === currentCampaignId) || {}).title || '';
    await notifyPlayers(`🗺️ Nuova quest: "${title}"${campTitle ? ` (campagna: ${campTitle})` : ''}`, 'quest');

    hideLoading();
    hideAddQuest();
    refreshQuestView();
  } catch (error) {
    hideLoading();
    console.error('Errore creazione quest:', error);
    alert('Errore nella creazione della quest');
  }
};

window.showQuestDetail = function(questId) {
  const quest = currentQuests.find(q => q.id === questId);
  if (!quest) return;
  
  const participants = quest.participants || [];
  const maxParticipants = quest.maxParticipants || 4;
  const slotsLeft = maxParticipants - participants.length;
  const isParticipant = participants.some(p => p.userId === currentUser.uid);
  const canJoin = !isParticipant && slotsLeft > 0;
  
  let participantsList = '';
  if (participants.length > 0) {
    participantsList = participants.map(p => `
      <div class="participant-item">
        <span class="participant-name">${p.username}</span>
      </div>
    `).join('');
  } else {
    participantsList = '<p style="color: var(--gray); text-align: center;">Nessun partecipante ancora</p>';
  }
  
  const joinButton = canJoin ?
    `<button onclick="joinQuest('${quest.id}')" class="btn-success">🎯 Iscriviti</button>` : '';
  
  const leaveButton = isParticipant ?
    `<button onclick="leaveQuest('${quest.id}')" class="btn-warning">↩️ Abbandona</button>` : '';
  
  const completeButton = currentUserRole === 'dm' ?
    `<button onclick="completeQuest('${quest.id}')" class="btn-info">✅ Completa Quest</button>` : '';
  
  const closeButton = currentUserRole === 'dm' ?
    `<button onclick="closeQuest('${quest.id}')" class="btn-danger">🚫 Chiudi Quest</button>` : '';
  
  const detailContent = document.getElementById('quest-detail-content');
  detailContent.innerHTML = `
    <h3>${quest.title}</h3>
    ${quest.questGiver ? `<p><strong>Affidata da:</strong> ${quest.questGiver}</p>` : ''}

    <div class="character-info">
      <p><strong>Descrizione:</strong></p>
      <p style="white-space: pre-wrap;">${quest.description}</p>
      
      ${quest.setting ? `
        <p><strong>Ambientazione:</strong></p>
        <p style="white-space: pre-wrap;">${quest.setting}</p>
      ` : ''}
      
      ${quest.rewards ? `
        <p><strong>Ricompense:</strong></p>
        <p style="white-space: pre-wrap;">${quest.rewards}</p>
      ` : ''}
      
      <p><strong>Difficoltà:</strong> <span class="difficulty-badge difficulty-${quest.difficulty}">${quest.difficulty}</span></p>
      
      <div class="slots-info">
        <div class="slots-count">${participants.length} / ${maxParticipants}</div>
        <div class="slots-label">Partecipanti</div>
        ${slotsLeft > 0 ? `<div style="color: var(--success); margin-top: 0.5rem;">${slotsLeft} slot disponibili</div>` : '<div style="color: var(--danger); margin-top: 0.5rem;">Completa</div>'}
      </div>
      
      <div class="quest-participants">
        <div class="participants-header">
          <h4 style="margin: 0;">Avventurieri Iscritti</h4>
        </div>
        <div class="participants-list">
          ${participantsList}
        </div>
      </div>
    </div>
    
    <div class="character-actions">
      ${joinButton}
      ${leaveButton}
      ${completeButton}
      ${closeButton}
      <button onclick="closeQuestDetail()" class="btn-secondary">Chiudi</button>
    </div>
  `;
  
  showElement('quest-detail');
};

window.closeQuestDetail = function() {
  hideElement('quest-detail');
};

window.joinQuest = async function(questId) {
  const quest = currentQuests.find(q => q.id === questId);
  if (!quest) return;
  
  const participants = quest.participants || [];
  if (participants.length >= quest.maxParticipants) {
    alert('La quest è già completa!');
    return;
  }
  
  if (participants.some(p => p.userId === currentUser.uid)) {
    alert('Sei già iscritto a questa quest!');
    return;
  }
  
  try {
    showLoading();
    
    const questRef = doc(db, 'quests', questId);
    await updateDoc(questRef, {
      participants: arrayUnion({
        userId: currentUser.uid,
        username: currentUsername
      })
    });
    
    hideLoading();
    closeQuestDetail();
    refreshQuestView();
    alert('Ti sei iscritto alla quest con successo!');
    
  } catch (error) {
    hideLoading();
    console.error('Errore iscrizione:', error);
    alert('Errore nell\'iscrizione alla quest');
  }
};

window.leaveQuest = async function(questId) {
  const quest = currentQuests.find(q => q.id === questId);
  if (!quest) return;
  
  if (!confirm('Vuoi davvero abbandonare questa quest?')) return;
  
  try {
    showLoading();
    
    const participants = quest.participants || [];
    const userParticipant = participants.find(p => p.userId === currentUser.uid);
    
    if (!userParticipant) {
      alert('Non sei iscritto a questa quest!');
      hideLoading();
      return;
    }
    
    const questRef = doc(db, 'quests', questId);
    await updateDoc(questRef, {
      participants: arrayRemove(userParticipant)
    });
    
    hideLoading();
    closeQuestDetail();
    refreshQuestView();
    alert('Hai abbandonato la quest.');
    
  } catch (error) {
    hideLoading();
    console.error('Errore abbandono:', error);
    alert('Errore nell\'abbandono della quest');
  }
};

window.completeQuest = async function(questId) {
  if (currentUserRole !== 'dm') {
    alert('Solo i Dungeon Master possono completare le quest!');
    return;
  }
  
  if (!confirm('Vuoi davvero segnare questa quest come completata? Questa azione è irreversibile!')) {
    return;
  }
  
  try {
    showLoading();
    
    const questRef = doc(db, 'quests', questId);
    await updateDoc(questRef, {
      isCompleted: true,
      completedAt: serverTimestamp()
    });
    
    hideLoading();
    closeQuestDetail();
    refreshQuestView();
    alert('Quest completata! ✅');
    
  } catch (error) {
    hideLoading();
    console.error('Errore:', error);
    alert('Errore durante il completamento della quest');
  }
};

window.closeQuest = async function(questId) {
  if (currentUserRole !== 'dm') {
    alert('Solo i Dungeon Master possono chiudere le quest!');
    return;
  }
  
  if (!confirm('Vuoi davvero chiudere questa quest senza completarla? La quest andrà nell\'archivio.')) {
    return;
  }
  
  try {
    showLoading();
    
    const questRef = doc(db, 'quests', questId);
    await updateDoc(questRef, {
      isClosed: true,
      closedAt: serverTimestamp()
    });
    
    hideLoading();
    closeQuestDetail();
    refreshQuestView();
    alert('Quest chiusa e archiviata! 🚫');
    
  } catch (error) {
    hideLoading();
    console.error('Errore:', error);
    alert('Errore durante la chiusura della quest');
  }
};

// === ARCHIVED QUESTS ===
async function loadArchivedQuests() {
  const listEl = document.getElementById('archive-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  
  try {
    const [qSnap, cSnap] = await Promise.all([
      getDocs(collection(db, 'quests')),
      getDocs(collection(db, 'campaigns'))
    ]);
    const campaigns = {};
    cSnap.forEach(d => { campaigns[d.id] = d.data(); });

    const archivedQuests = [];
    qSnap.forEach(d => {
      const questData = { id: d.id, ...d.data() };
      if (questData.isCompleted || questData.isClosed) archivedQuests.push(questData);
    });

    archivedQuests.sort((a, b) => {
      const dateA = a.completedAt || a.closedAt;
      const dateB = b.completedAt || b.closedAt;
      return ((dateB && dateB.seconds) || 0) - ((dateA && dateA.seconds) || 0);
    });

    renderArchivedQuests(archivedQuests, campaigns);
  } catch (error) {
    console.error('Errore caricamento libro mastro:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento del Libro Mastro</p></div>';
  }
}

function questArchiveCard(quest) {
  const participants = quest.participants || [];
  const status = quest.isCompleted
    ? '<span class="card-status status-completed">✅ Completata</span>'
    : '<span class="card-status" style="background: var(--gray)">🚫 Chiusa</span>';
  const date = quest.completedAt || quest.closedAt;
  const dateStr = date ? new Date(date.seconds * 1000).toLocaleDateString('it-IT') : '';
  const names = participants.length > 0 ? participants.map(p => p.username).join(', ') : 'Nessuno';
  return `
    <div class="card" onclick="showArchivedQuestDetail('${quest.id}')">
      <h3>${quest.title}</h3>
      ${quest.questGiver ? `<p><strong>Affidata da:</strong> ${quest.questGiver}</p>` : ''}
      <p>${quest.description.substring(0, 100)}${quest.description.length > 100 ? '...' : ''}</p>
      <p><strong>Partecipanti (${participants.length}):</strong> ${names}</p>
      ${dateStr ? `<p><strong>${quest.isCompleted ? 'Completata il' : 'Chiusa il'}:</strong> ${dateStr}</p>` : ''}
      <span class="difficulty-badge difficulty-${quest.difficulty}">${quest.difficulty}</span>
      ${status}
    </div>`;
}

function renderArchivedQuests(quests, campaigns) {
  const listEl = document.getElementById('archive-list');

  if (quests.length === 0) {
    listEl.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">📖</div>
        <p>Il Libro Mastro è vuoto.</p>
        <p>Le sessioni completate o chiuse appariranno qui, divise per campagna.</p>
      </div>`;
    return;
  }

  // Raggruppa per campagna
  const groups = {};
  quests.forEach(q => {
    const key = q.campaignId || '_none';
    (groups[key] = groups[key] || []).push(q);
  });

  const keys = Object.keys(groups).sort((a, b) => {
    if (a === '_none') return 1;
    if (b === '_none') return -1;
    const ta = (campaigns[a] || {}).title || '';
    const tb = (campaigns[b] || {}).title || '';
    return ta.localeCompare(tb);
  });

  listEl.innerHTML = keys.map(key => {
    const title = key === '_none'
      ? 'Senza campagna'
      : ((campaigns[key] || {}).title || groups[key][0].campaignTitle || 'Campagna');
    const cards = groups[key].map(questArchiveCard).join('');
    return `
      <div class="guild-player">
        <h3 style="margin-bottom:0.5rem;">📖 ${title}</h3>
        <div class="cards-grid">${cards}</div>
      </div>`;
  }).join('');
}

window.showArchivedQuestDetail = function(questId) {
  // Load quest from DB since it's not in currentQuests
  getDoc(doc(db, 'quests', questId)).then(docSnap => {
    if (!docSnap.exists()) return;
    
    const quest = { id: docSnap.id, ...docSnap.data() };
    const participants = quest.participants || [];
    
    let participantsList = '';
    if (participants.length > 0) {
      participantsList = participants.map(p => `
        <div class="participant-item">
          <span class="participant-name">${p.username}</span>
        </div>
      `).join('');
    } else {
      participantsList = '<p style="color: var(--gray); text-align: center;">Nessun partecipante</p>';
    }
    
    const status = quest.isCompleted ? '✅ Completata' : '🚫 Chiusa';
    const date = quest.completedAt || quest.closedAt;
    const dateStr = date ? new Date(date.seconds * 1000).toLocaleDateString('it-IT') : '';
    
    const detailContent = document.getElementById('quest-detail-content');
    detailContent.innerHTML = `
      <h3>${quest.title}</h3>
      ${quest.questGiver ? `<p><strong>Affidata da:</strong> ${quest.questGiver}</p>` : ''}
      <p><strong>Stato:</strong> ${status} ${dateStr ? `(${dateStr})` : ''}</p>

      <div class="character-info">
        <p><strong>Descrizione:</strong></p>
        <p style="white-space: pre-wrap;">${quest.description}</p>
        
        ${quest.setting ? `
          <p><strong>Ambientazione:</strong></p>
          <p style="white-space: pre-wrap;">${quest.setting}</p>
        ` : ''}
        
        ${quest.rewards ? `
          <p><strong>Ricompense:</strong></p>
          <p style="white-space: pre-wrap;">${quest.rewards}</p>
        ` : ''}
        
        <p><strong>Difficoltà:</strong> <span class="difficulty-badge difficulty-${quest.difficulty}">${quest.difficulty}</span></p>
        
        <div class="quest-participants">
          <div class="participants-header">
            <h4 style="margin: 0;">Avventurieri</h4>
          </div>
          <div class="participants-list">
            ${participantsList}
          </div>
        </div>
      </div>
      
      <div class="character-actions">
        <button onclick="closeQuestDetail()" class="btn-secondary">Chiudi</button>
      </div>
    `;
    
    showElement('quest-detail');
  });
};

// === FALLEN HEROES ===
async function loadFallenHeroes() {
  const listEl = document.getElementById('fallen-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  
  try {
    const snapshot = await getDocs(collection(db, 'characters'));
    fallenHeroes = [];
    
    snapshot.forEach(doc => {
      const charData = { id: doc.id, ...doc.data() };
      if (charData.isDead) {
        fallenHeroes.push(charData);
      }
    });
    
    fallenHeroes.sort((a, b) => {
      if (!a.deathDate || !b.deathDate) return 0;
      return b.deathDate.seconds - a.deathDate.seconds;
    });
    
    renderFallenHeroes();
  } catch (error) {
    console.error('Errore caricamento fallen heroes:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento</p></div>';
  }
}

function renderFallenHeroes() {
  const listEl = document.getElementById('fallen-list');
  
  if (fallenHeroes.length === 0) {
    listEl.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">☠️</div>
        <p>Nessun eroe caduto... per ora.</p>
        <p>La hall è vuota, ma le leggende attendono di essere scritte.</p>
      </div>
    `;
    return;
  }
  
  listEl.innerHTML = fallenHeroes.map(char => {
    const deathDate = char.deathDate ? 
      new Date(char.deathDate.seconds * 1000).toLocaleDateString('it-IT') : 
      'Data sconosciuta';
    
    return `
      <div class="card fallen-card">
        <h3>⚰️ ${char.name}</h3>
        <p><strong>Classe:</strong> ${char.class}${char.subclass ? ` (${char.subclass})` : ''}</p>
        <p><strong>Livello:</strong> ${char.level || 1}</p>
        <p><strong>Razza:</strong> ${char.race}</p>
        <p><strong>Caduto il:</strong> ${deathDate}</p>
        <span class="card-status status-dead">💀 Caduto in battaglia</span>
      </div>
    `;
  }).join('');
}

// === GILDA (tutti i giocatori e i loro eroi) ===
async function loadGuild() {
  const listEl = document.getElementById('guild-list');
  listEl.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';

  try {
    const [usersSnap, charsSnap] = await Promise.all([
      getDocs(collection(db, 'users')),
      getDocs(collection(db, 'characters'))
    ]);

    const usersById = {};
    usersSnap.forEach(d => { usersById[d.id] = d.data(); });
    guildUsers = usersById;

    guildCharacters = [];
    const roster = [];
    charsSnap.forEach(d => {
      const char = { id: d.id, ...d.data() };
      guildCharacters.push(char);
      if (char.isDead) return; // i caduti stanno nella Hall of Fallen Heroes
      roster.push({ char, user: usersById[char.userId] || {} });
    });

    renderGuild(roster);
  } catch (error) {
    console.error('Errore caricamento gilda:', error);
    listEl.innerHTML = '<div class="empty-state"><p>Errore nel caricamento</p></div>';
  }
}

function renderGuild(roster) {
  const listEl = document.getElementById('guild-list');

  if (roster.length === 0) {
    listEl.innerHTML = '<div class="empty-state"><p>Nessun personaggio nella gilda.</p></div>';
    return;
  }

  // Ordina: prima i personaggi dei DM, poi per nome PG
  roster.sort((a, b) => {
    const da = (a.user.role === 'dm') ? 1 : 0;
    const db_ = (b.user.role === 'dm') ? 1 : 0;
    if (db_ - da !== 0) return db_ - da;
    return (a.char.name || '').localeCompare(b.char.name || '');
  });

  listEl.innerHTML = roster.map(({ char, user }) => {
    const c = char;
    const roleBadge = user.role === 'dm' ? ' <span class="difficulty-badge difficulty-Epica">DM</span>' : '';
    const isMe = c.userId === currentUser.uid ? ' <span style="color: var(--gray);">(tu)</span>' : '';
    const avatar = user.photo
      ? `<img src="${user.photo}" class="avatar-small" alt="" />`
      : `<span class="avatar-small avatar-placeholder">${(c.name || '?')[0].toUpperCase()}</span>`;

    return `
      <div class="card" style="cursor:pointer;" onclick="showGuildCharacter('${c.id}')">
        <h3 style="display: flex; align-items: center; gap: 0.5rem;">${avatar} ${c.name}${roleBadge}${isMe}</h3>
        <p><strong>Classe:</strong> ${c.class}${c.subclass ? ` (${c.subclass})` : ''}</p>
        <p><strong>Livello:</strong> ${c.level || 1} · <strong>Razza:</strong> ${c.race}</p>
      </div>
    `;
  }).join('');
}

window.showGuildCharacter = function(charId) {
  const char = guildCharacters.find(c => c.id === charId);
  if (!char) return;

  const stats = effectiveStats(char);
  const combat = char.combat || {};
  const statNames = STAT_LABELS;
  const owner = guildUsers[char.userId] || {};
  const playerName = owner.username || 'Sconosciuto';

  const statsGrid = Object.entries(statNames).map(([key, label]) => {
    const val = stats[key] || 10;
    return `
      <div class="stat-box">
        <span class="stat-label">${label}</span>
        <span class="stat-value">${val}</span>
        <span class="stat-modifier">${formatModifier(calculateModifier(val))}</span>
      </div>
    `;
  }).join('');

  document.getElementById('character-detail-content').innerHTML = `
    <h3>${char.name}</h3>
    <div class="character-info">
      <p><strong>Giocatore/Giocatrice:</strong> ${playerName}</p>
      <p><strong>Classe:</strong> ${char.class}${char.subclass ? ` (${char.subclass})` : ''}</p>
      <p><strong>Livello:</strong> ${char.level || 1}</p>
      <p><strong>Razza:</strong> ${char.race}</p>
      ${char.background ? `<p><strong>Background:</strong> ${char.background}</p>` : ''}

      <h4 style="margin-top: 1.5rem;">Caratteristiche</h4>
      <div class="character-stats-grid">${statsGrid}</div>

      <h4 style="margin-top: 1.5rem;">Combattimento</h4>
      <p><strong>Classe Armatura:</strong> ${combat.ac || 10}</p>
      <p><strong>Punti Ferita Max:</strong> ${effectiveHpMax(char)}</p>
      <p><strong>Bonus Competenza:</strong> +${char.proficiencyBonus || calculateProficiencyBonus(char.level || 1)}</p>

      ${char.feats && char.feats.length > 0 ? `
        <h4 style="margin-top: 1.5rem;">Talenti</h4>
        ${char.feats.map(f => `<p><strong>${f.name}</strong>${f.description ? `: ${f.description}` : ''}</p>`).join('')}
      ` : ''}
    </div>

    <div class="character-actions">
      <button onclick="hideCharacterDetail()" class="btn-secondary">Chiudi</button>
    </div>
  `;

  showElement('character-detail');
};

// ===================================================================
// PROFILO + FOTO
// ===================================================================
function loadProfile() {
  const el = document.getElementById('profile-content');
  const avatar = currentUserPhoto
    ? `<img src="${currentUserPhoto}" class="avatar-large" alt="avatar" />`
    : `<div class="avatar-large avatar-placeholder">${(currentUsername[0] || '?').toUpperCase()}</div>`;
  el.innerHTML = `
    <div class="profile-box">
      ${avatar}
      <h3>${currentUsername}${currentUserRole === 'dm' ? ' <span class="difficulty-badge difficulty-Epica">DM</span>' : ''}</h3>
      <p style="color: var(--gray);">${currentUser.email}</p>
      <div class="character-actions">
        <button class="btn-info" onclick="document.getElementById('avatar-input').click()">📷 Cambia Foto</button>
        ${currentUserPhoto ? `<button class="btn-danger" onclick="removeAvatar()">🗑️ Rimuovi Foto</button>` : ''}
      </div>
    </div>
  `;
}

window.handleAvatarUpload = function(event) {
  const file = event.target.files && event.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = new Image();
    img.onload = async () => {
      const size = 256;
      const min = Math.min(img.width, img.height);
      const canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, (img.width - min) / 2, (img.height - min) / 2, min, min, 0, 0, size, size);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
      try {
        showLoading();
        await updateDoc(doc(db, 'users', currentUser.uid), { photo: dataUrl });
        currentUserPhoto = dataUrl;
        hideLoading();
        loadProfile();
      } catch (err) {
        hideLoading();
        console.error('Errore salvataggio foto:', err);
        alert('Errore nel salvataggio della foto');
      }
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
  event.target.value = '';
};

window.removeAvatar = async function() {
  if (!confirm('Rimuovere la foto profilo?')) return;
  try {
    showLoading();
    await updateDoc(doc(db, 'users', currentUser.uid), { photo: '' });
    currentUserPhoto = '';
    hideLoading();
    loadProfile();
  } catch (e) {
    hideLoading();
    console.error(e);
    alert('Errore');
  }
};

// ===================================================================
// HELPER INVENTARIO / PERSONAGGIO ATTIVO
// ===================================================================
async function fetchMyCharacter() {
  const snap = await getDocs(query(collection(db, 'characters'), where('userId', '==', currentUser.uid)));
  const chars = [];
  snap.forEach(d => chars.push({ id: d.id, ...d.data() }));
  return chars.find(c => !c.isDead) || null;
}

function addItemToList(items, entry) {
  const list = (items || []).map(i => ({ ...i }));
  const found = list.find(i => i.name === entry.name && (i.value || 0) === (entry.value || 0));
  if (found) {
    found.qty = (found.qty || 0) + entry.qty;
    if (entry.details && !found.details) found.details = entry.details;
  } else {
    list.push({ name: entry.name, qty: entry.qty, value: entry.value || 0, category: entry.category || '', details: entry.details || '' });
  }
  return list;
}

function removeItemFromList(items, name, qty) {
  const list = (items || []).map(i => ({ ...i }));
  const idx = list.findIndex(i => i.name === name);
  if (idx === -1 || (list[idx].qty || 0) < qty) return null;
  list[idx].qty -= qty;
  if (list[idx].qty <= 0) list.splice(idx, 1);
  return list;
}

// ===================================================================
// MERCATO
// ===================================================================
const DEFAULT_MARKET = [
  { name: 'Pozione di Cura', category: 'Pozioni', price: 50, stock: null, details: 'Recupera 2d4+2 punti ferita bevendola (azione).' },
  { name: 'Antitossina', category: 'Pozioni', price: 50, stock: null, details: 'Vantaggio ai tiri salvezza contro il veleno per 1 ora.' },
  { name: 'Veleno Base (fiala)', category: 'Veleni', price: 100, stock: null, details: 'Applicato a un\'arma: +1d4 danni da veleno per 1 minuto (TS Costituzione CD 10).' },
  { name: 'Pugnale', category: 'Armi', price: 2, stock: null, details: 'Arma semplice. 1d4 perforante. Accurata, leggera, da lancio (6/18 m).' },
  { name: 'Spada Corta', category: 'Armi', price: 10, stock: null, details: 'Arma da guerra. 1d6 perforante. Accurata, leggera.' },
  { name: 'Spada Lunga', category: 'Armi', price: 15, stock: null, details: 'Arma da guerra. 1d8 tagliente (1d10 impugnata a due mani), versatile.' },
  { name: 'Spadone', category: 'Armi', price: 50, stock: null, details: 'Arma da guerra a due mani. 2d6 tagliente, pesante.' },
  { name: 'Ascia da Battaglia', category: 'Armi', price: 10, stock: null, details: 'Arma da guerra. 1d8 tagliente (1d10 a due mani), versatile.' },
  { name: 'Ascia Bipenne', category: 'Armi', price: 30, stock: null, details: 'Arma da guerra a due mani. 1d12 tagliente, pesante.' },
  { name: 'Mazza', category: 'Armi', price: 5, stock: null, details: 'Arma semplice. 1d6 contundente.' },
  { name: 'Martello da Guerra', category: 'Armi', price: 15, stock: null, details: 'Arma da guerra. 1d8 contundente (1d10 a due mani), versatile.' },
  { name: 'Maglio', category: 'Armi', price: 10, stock: null, details: 'Arma da guerra a due mani. 2d6 contundente, pesante.' },
  { name: 'Lancia', category: 'Armi', price: 1, stock: null, details: 'Arma semplice. 1d6 perforante, da lancio (6/18 m), versatile.' },
  { name: 'Alabarda', category: 'Armi', price: 20, stock: null, details: 'Arma da guerra a due mani. 1d10 tagliente, portata, pesante.' },
  { name: 'Arco Corto', category: 'Armi', price: 25, stock: null, details: 'Arma semplice a distanza. 1d6 perforante (24/96 m).' },
  { name: 'Arco Lungo', category: 'Armi', price: 50, stock: null, details: 'Arma da guerra a distanza. 1d8 perforante (45/180 m), pesante.' },
  { name: 'Balestra Leggera', category: 'Armi', price: 25, stock: null, details: 'Arma semplice a distanza. 1d8 perforante (24/96 m), ricarica.' },
  { name: 'Balestra Pesante', category: 'Armi', price: 50, stock: null, details: 'Arma da guerra a distanza. 1d10 perforante (30/120 m), pesante, ricarica.' },
  { name: 'Armatura Imbottita', category: 'Armature', price: 5, stock: null, details: 'Leggera. CA 11 + mod. DES. Svantaggio a Furtività.' },
  { name: 'Armatura di Cuoio', category: 'Armature', price: 10, stock: null, details: 'Leggera. CA 11 + mod. DES.' },
  { name: 'Cuoio Borchiato', category: 'Armature', price: 45, stock: null, details: 'Leggera. CA 12 + mod. DES.' },
  { name: 'Corazza di Scaglie', category: 'Armature', price: 50, stock: null, details: 'Media. CA 14 + mod. DES (max +2). Svantaggio a Furtività.' },
  { name: 'Corpetto (Corazza a Bande)', category: 'Armature', price: 400, stock: null, details: 'Media. CA 14 + mod. DES (max +2).' },
  { name: 'Mezza Armatura', category: 'Armature', price: 750, stock: null, details: 'Media. CA 15 + mod. DES (max +2). Svantaggio a Furtività.' },
  { name: 'Cotta di Maglia', category: 'Armature', price: 75, stock: null, details: 'Pesante. CA 16. Richiede FOR 13. Svantaggio a Furtività.' },
  { name: 'Armatura a Piastre', category: 'Armature', price: 1500, stock: null, details: 'Pesante. CA 18. Richiede FOR 15. Svantaggio a Furtività.' },
  { name: 'Scudo', category: 'Armature', price: 10, stock: null, details: '+2 alla Classe Armatura.' },
  { name: 'Pozione di Cura Superiore', category: 'Pozioni Rare', price: 150, stock: 5, details: 'Recupera 4d4+4 punti ferita bevendola (azione).' },
  { name: 'Arma +1', category: 'Oggetti Magici', price: 1000, stock: 2, details: '+1 ai tiri per colpire e ai danni effettuati con quest\'arma.' },
  { name: 'Armatura +1', category: 'Oggetti Magici', price: 1500, stock: 1, details: '+1 alla Classe Armatura fornita dall\'armatura.' },
  { name: 'Scudo +1', category: 'Oggetti Magici', price: 500, stock: 1, details: '+3 alla Classe Armatura (scudo potenziato).' }
];

function isInfinite(item) {
  return item.stock === null || item.stock === undefined;
}

async function loadMarket() {
  const el = document.getElementById('market-content');
  el.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  try {
    const catSnap = await getDocs(collection(db, 'market_items'));
    marketItems = [];
    catSnap.forEach(d => marketItems.push({ id: d.id, ...d.data() }));

    const lsnap = await getDocs(collection(db, 'market_listings'));
    const listings = [];
    lsnap.forEach(d => {
      const l = { id: d.id, ...d.data() };
      if (l.status === 'active') listings.push(l);
    });

    // Scambi che mi coinvolgono (query a campo singolo per non richiedere indici)
    const [tTo, tFrom] = await Promise.all([
      getDocs(query(collection(db, 'trades'), where('toUserId', '==', currentUser.uid))),
      getDocs(query(collection(db, 'trades'), where('fromUserId', '==', currentUser.uid)))
    ]);
    const trades = [];
    const seen = {};
    [tTo, tFrom].forEach(snap => snap.forEach(d => {
      if (seen[d.id]) return;
      const t = { id: d.id, ...d.data() };
      if (t.status === 'pending') { trades.push(t); seen[d.id] = true; }
    }));

    const myChar = await fetchMyCharacter();
    renderMarket(myChar, listings, trades);
  } catch (e) {
    console.error('Errore mercato:', e);
    el.innerHTML = '<div class="empty-state"><p>Errore nel caricamento del mercato</p></div>';
  }
}

function renderMarket(myChar, listings, trades) {
  const el = document.getElementById('market-content');
  const gp = myChar ? (myChar.gp || 0) : 0;

  const cats = {};
  marketItems.forEach(i => { (cats[i.category || 'Altro'] = cats[i.category || 'Altro'] || []).push(i); });

  const catalogHtml = marketItems.length === 0
    ? '<p style="color: var(--gray);">Il mercato è vuoto.</p>'
    : Object.keys(cats).sort().map(cat => `
        <h4 style="margin-top: 1rem;">${cat}</h4>
        ${cats[cat].map(i => {
          const stockLabel = isInfinite(i) ? '∞' : i.stock;
          const soldout = !isInfinite(i) && (i.stock || 0) <= 0;
          return `
            <div class="market-row">
              <div style="flex:1; min-width: 60%;">
                <strong>${i.name}</strong> — ${i.price} Oro <span style="color: var(--gray);">· scorte: ${stockLabel}</span>
                ${i.details ? `<br><span style="color: var(--gray); font-size: 0.9rem;">${i.details}</span>` : ''}
              </div>
              <div>
                ${soldout ? '<span class="card-status status-dead">Esaurito</span>'
                  : (myChar ? `<button class="btn-success" onclick="buyMarketItem('${i.id}')">🪙 Compra</button>` : '')}
                ${currentUserRole === 'dm' ? `<button class="btn-warning" onclick="dmEditMarketItem('${i.id}')">✏️</button><button class="btn-danger" onclick="dmDeleteMarketItem('${i.id}')">✕</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      `).join('');

  const listingsHtml = listings.length === 0
    ? '<p style="color: var(--gray);">Nessun oggetto in vendita dai giocatori.</p>'
    : listings.map(l => {
        const mine = l.sellerId === currentUser.uid;
        return `
          <div class="market-row">
            <div><strong>${l.qty}× ${l.name}</strong> — ${l.price} Oro <span style="color: var(--gray);">· da ${l.sellerName}</span></div>
            <div>
              ${mine || currentUserRole === 'dm' ? `<button class="btn-warning" onclick="cancelListing('${l.id}')">Ritira</button>` : ''}
              ${!mine && myChar ? `<button class="btn-success" onclick="buyListing('${l.id}')">🪙 Compra</button>` : ''}
            </div>
          </div>
        `;
      }).join('');

  const tradesHtml = trades.length === 0
    ? '<p style="color: var(--gray);">Nessuno scambio in sospeso.</p>'
    : trades.map(t => {
        const incoming = t.toUserId === currentUser.uid;
        const giveStr = [...t.giveItems.map(i => `${i.qty}× ${i.name}`), t.giveGp ? `${t.giveGp} gp` : ''].filter(Boolean).join(', ') || 'niente';
        const wantStr = [...t.wantItems.map(i => `${i.qty}× ${i.name}`), t.wantGp ? `${t.wantGp} gp` : ''].filter(Boolean).join(', ') || 'niente';
        return `
          <div class="card">
            <p><strong>${t.fromUserName}</strong> → <strong>${t.toUserName}</strong></p>
            <p>Offre: ${giveStr}</p>
            <p>Chiede: ${wantStr}</p>
            <div class="character-actions">
              ${incoming
                ? `<button class="btn-success" onclick="acceptTrade('${t.id}')">Accetta</button><button class="btn-danger" onclick="rejectTrade('${t.id}')">Rifiuta</button>`
                : `<button class="btn-warning" onclick="cancelTrade('${t.id}')">Annulla</button>`}
            </div>
          </div>
        `;
      }).join('');

  const dmControls = currentUserRole === 'dm' ? `
    <div class="character-actions" style="margin-bottom: 1rem;">
      ${marketItems.length === 0 ? `<button class="btn-success btn-lg" onclick="seedMarket()">🌱 Inizializza Catalogo Standard</button>` : ''}
      <button class="btn-info btn-lg" onclick="dmAddMarketItem()">➕ Aggiungi Oggetto</button>
    </div>` : '';

  el.innerHTML = `
    <div class="profile-box" style="margin-bottom:1rem;">
      <p><strong>Il tuo oro:</strong> 🪙 ${gp} Oro ${myChar ? `(${myChar.name})` : '(nessun personaggio attivo)'}</p>
      ${myChar ? `<div class="character-actions"><button class="btn-info" onclick="sellItem()">🏷️ Vendi un Oggetto</button><button class="btn-info" onclick="openTradeForm()">🔄 Proponi Scambio</button></div>` : ''}
    </div>
    ${dmControls}
    <h3>🏪 Catalogo della Gilda</h3>
    ${catalogHtml}
    <h3 style="margin-top: 1.5rem;">🤝 Bacheca Giocatori</h3>
    ${listingsHtml}
    <h3 style="margin-top: 1.5rem;">🔄 Scambi in Sospeso</h3>
    ${tradesHtml}
  `;
}

window.buyMarketItem = async function(itemId) {
  const item = marketItems.find(i => i.id === itemId);
  if (!item) return;
  const myChar = await fetchMyCharacter();
  if (!myChar) { alert('Devi avere un personaggio attivo per comprare.'); return; }
  const qty = parseInt(prompt(`Quante unità di "${item.name}"? (${item.price} Oro l'una)`, '1'));
  if (isNaN(qty) || qty < 1) return;
  const cost = item.price * qty;
  const gp = myChar.gp || 0;
  if (gp < cost) { alert(`Oro insufficiente! Hai ${gp} gp, servono ${cost} gp.`); return; }
  const finite = !isInfinite(item);
  if (finite && (item.stock || 0) < qty) { alert(`Scorte insufficienti! Disponibili: ${item.stock}.`); return; }

  try {
    showLoading();
    if (finite) {
      await runTransaction(db, async (tx) => {
        const ref = doc(db, 'market_items', itemId);
        const snap = await tx.get(ref);
        const stock = (snap.data() || {}).stock || 0;
        if (stock < qty) throw new Error('OUT_OF_STOCK');
        tx.update(ref, { stock: stock - qty });
      });
    }
    const items = addItemToList(myChar.items || [], { name: item.name, qty, value: item.price, category: item.category, details: item.details || '' });
    await updateDoc(doc(db, 'characters', myChar.id), { gp: gp - cost, items });
    await logAudit({ charId: myChar.id, charName: myChar.name, action: 'buy', message: `Acquistato ${qty}× ${item.name} dal mercato`, gpDelta: -cost });
    hideLoading();
    alert(`Acquistato: ${qty}× ${item.name} per ${cost} gp.`);
    loadMarket();
  } catch (err) {
    hideLoading();
    if (err.message === 'OUT_OF_STOCK') alert('Scorte esaurite nel frattempo!');
    else { console.error(err); alert('Errore nell\'acquisto'); }
    loadMarket();
  }
};

window.seedMarket = async function() {
  if (currentUserRole !== 'dm') { alert('Solo i DM!'); return; }
  if (!confirm('Inizializzare il catalogo con gli oggetti standard?')) return;
  try {
    showLoading();
    const batch = writeBatch(db);
    DEFAULT_MARKET.forEach(it => batch.set(doc(collection(db, 'market_items')), it));
    await batch.commit();
    hideLoading();
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.dmAddMarketItem = async function() {
  if (currentUserRole !== 'dm') return;
  const name = prompt('Nome oggetto:');
  if (!name || !name.trim()) return;
  const category = (prompt('Categoria (es. Pozioni, Armi, Armature):', 'Altro') || 'Altro').trim();
  const price = parseInt(prompt('Prezzo in gp:', '10'));
  if (isNaN(price)) return;
  const stockStr = prompt('Scorte? (numero, oppure "inf" per infinite):', 'inf');
  const stock = (stockStr && stockStr.trim().toLowerCase() === 'inf') ? null : (parseInt(stockStr) || 0);
  const details = (prompt('Dettagli/descrizione dell\'oggetto (es. "Recupera 2d4+2 PF"):', '') || '').trim();
  if (!details) { alert('Inserisci una breve descrizione dell\'oggetto.'); return; }
  try { showLoading(); await addDoc(collection(db, 'market_items'), { name: name.trim(), category, price, stock, details }); hideLoading(); loadMarket(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.dmEditMarketItem = async function(itemId) {
  if (currentUserRole !== 'dm') return;
  const item = marketItems.find(i => i.id === itemId);
  if (!item) return;
  const price = parseInt(prompt('Nuovo prezzo (gp):', String(item.price)));
  if (isNaN(price)) return;
  const stockStr = prompt('Scorte (numero o "inf"):', isInfinite(item) ? 'inf' : String(item.stock));
  const stock = (stockStr && stockStr.trim().toLowerCase() === 'inf') ? null : (parseInt(stockStr) || 0);
  const details = (prompt('Dettagli/descrizione:', item.details || '') || '').trim();
  try { showLoading(); await updateDoc(doc(db, 'market_items', itemId), { price, stock, details }); hideLoading(); loadMarket(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.dmDeleteMarketItem = async function(itemId) {
  if (currentUserRole !== 'dm') return;
  if (!confirm('Eliminare questo oggetto dal catalogo?')) return;
  try { showLoading(); await deleteDoc(doc(db, 'market_items', itemId)); hideLoading(); loadMarket(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.dmGrantGold = async function(charId) {
  if (currentUserRole !== 'dm') { alert('Solo i DM!'); return; }
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) return;
  const amt = parseInt(prompt(`Oro da aggiungere a ${char.name} (negativo per togliere):`, '100'));
  if (isNaN(amt)) return;
  const gp = Math.max(0, (char.gp || 0) + amt);
  try {
    showLoading();
    await updateDoc(doc(db, 'characters', charId), { gp });
    await logAudit({ charId, charName: char.name, action: 'dm-gold', message: 'Oro modificato dal DM', gpDelta: amt });
    hideLoading();
    hideCharacterDetail();
    loadCharacters();
    alert(`Oro aggiornato: ${gp} gp`);
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

// ===================================================================
// VENDITE TRA GIOCATORI (LISTINGS)
// ===================================================================
window.sellItem = async function() {
  const myChar = await fetchMyCharacter();
  if (!myChar) { alert('Serve un personaggio attivo.'); return; }
  const items = myChar.items || [];
  if (items.length === 0) { alert('Non hai oggetti da vendere.'); return; }
  const list = items.map((it, i) => `${i + 1}) ${it.qty}× ${it.name}`).join('\n');
  const choice = parseInt(prompt(`Quale oggetto vendere?\n${list}`, '1'));
  if (isNaN(choice) || choice < 1 || choice > items.length) return;
  const it = items[choice - 1];
  const qty = parseInt(prompt(`Quante unità? (hai ${it.qty})`, '1'));
  if (isNaN(qty) || qty < 1 || qty > it.qty) { alert('Quantità non valida.'); return; }
  const price = parseInt(prompt(`Prezzo totale in gp per ${qty}× ${it.name}:`, String((it.value || 0) * qty)));
  if (isNaN(price) || price < 0) return;
  const newItems = removeItemFromList(items, it.name, qty);
  if (!newItems) { alert('Errore quantità.'); return; }
  try {
    showLoading();
    await updateDoc(doc(db, 'characters', myChar.id), { items: newItems });
    await addDoc(collection(db, 'market_listings'), {
      sellerId: currentUser.uid, sellerName: currentUsername,
      sellerCharId: myChar.id, sellerCharName: myChar.name,
      name: it.name, qty, price, unitValue: it.value || 0, category: it.category || '', details: it.details || '',
      status: 'active', createdAt: serverTimestamp()
    });
    await notifyDM(`🏷️ ${currentUsername} ha messo in vendita ${qty}× ${it.name} per ${price} gp.`, 'listing');
    await logAudit({ charId: myChar.id, charName: myChar.name, action: 'sell-list', message: `Messo in vendita ${qty}× ${it.name} a ${price} gp` });
    hideLoading();
    alert('Oggetto messo in vendita!');
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.buyListing = async function(listingId) {
  const myChar = await fetchMyCharacter();
  if (!myChar) { alert('Serve un personaggio attivo.'); return; }
  const lref = doc(db, 'market_listings', listingId);
  const lsnap = await getDoc(lref);
  if (!lsnap.exists() || lsnap.data().status !== 'active') { alert('Offerta non più disponibile.'); loadMarket(); return; }
  const l = lsnap.data();
  if (l.sellerId === currentUser.uid) { alert('Non puoi comprare un tuo oggetto.'); return; }
  const cost = l.price;
  if ((myChar.gp || 0) < cost) { alert(`Oro insufficiente! Hai ${myChar.gp || 0} gp, servono ${cost}.`); return; }
  if (!confirm(`Comprare ${l.qty}× ${l.name} per ${cost} gp da ${l.sellerName}?`)) return;
  try {
    showLoading();
    const scharRef = doc(db, 'characters', l.sellerCharId);
    const scharSnap = await getDoc(scharRef);
    const batch = writeBatch(db);
    const buyerItems = addItemToList(myChar.items || [], { name: l.name, qty: l.qty, value: l.unitValue || 0, category: l.category || '', details: l.details || '' });
    batch.update(doc(db, 'characters', myChar.id), { gp: (myChar.gp || 0) - cost, items: buyerItems });
    if (scharSnap.exists()) batch.update(scharRef, { gp: (scharSnap.data().gp || 0) + cost });
    batch.update(lref, { status: 'sold', buyerId: currentUser.uid, buyerName: currentUsername, soldAt: serverTimestamp() });
    await batch.commit();
    await notifyDM(`💰 ${currentUsername} ha comprato ${l.qty}× ${l.name} da ${l.sellerName} per ${cost} gp.`, 'sale');
    await logAudit({ charId: myChar.id, charName: myChar.name, action: 'buy-listing', message: `Comprato ${l.qty}× ${l.name} da ${l.sellerName}`, gpDelta: -cost });
    await logAudit({ charId: l.sellerCharId, charName: l.sellerCharName, action: 'sold', message: `Venduto ${l.qty}× ${l.name} a ${currentUsername}`, gpDelta: cost });
    hideLoading();
    alert('Acquisto completato!');
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Errore nell\'acquisto'); }
};

window.cancelListing = async function(listingId) {
  const lsnap = await getDoc(doc(db, 'market_listings', listingId));
  if (!lsnap.exists()) return;
  const data = lsnap.data();
  if (data.sellerId !== currentUser.uid && currentUserRole !== 'dm') { alert('Non puoi ritirare questa offerta.'); return; }
  if (!confirm('Ritirare l\'offerta e riprendere l\'oggetto?')) return;
  try {
    showLoading();
    const tSnap = await getDoc(doc(db, 'characters', data.sellerCharId));
    const batch = writeBatch(db);
    if (tSnap.exists()) {
      const items = addItemToList(tSnap.data().items || [], { name: data.name, qty: data.qty, value: data.unitValue || 0, category: data.category || '', details: data.details || '' });
      batch.update(doc(db, 'characters', data.sellerCharId), { items });
    }
    batch.update(doc(db, 'market_listings', listingId), { status: 'cancelled' });
    await batch.commit();
    hideLoading();
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

// ===================================================================
// SCAMBI TRA GIOCATORI (TRADES)
// ===================================================================
window.hideGeneric = function() { hideElement('generic-modal'); };

window.openTradeForm = async function() {
  const myChar = await fetchMyCharacter();
  if (!myChar) { alert('Serve un personaggio attivo per scambiare.'); return; }
  const [usnap, csnap] = await Promise.all([getDocs(collection(db, 'users')), getDocs(collection(db, 'characters'))]);
  const users = {};
  usnap.forEach(d => users[d.id] = d.data());
  const charByUser = {};
  csnap.forEach(d => { const c = { id: d.id, ...d.data() }; if (!c.isDead) charByUser[c.userId] = c; });
  const targets = Object.keys(charByUser).filter(uid => uid !== currentUser.uid);
  if (targets.length === 0) { alert('Nessun altro giocatore con un personaggio attivo.'); return; }

  window._tradeCtx = { myChar, charByUser, users };
  const targetOptions = targets.map(uid => `<option value="${uid}">${users[uid] ? users[uid].username : '?'} (${charByUser[uid].name})</option>`).join('');
  const myItems = myChar.items || [];
  const giveItemsHtml = myItems.length ? myItems.map(it => `
    <label style="display:block; margin:0.25rem 0;"><input type="checkbox" class="give-item" data-name="${it.name}" data-value="${it.value || 0}" data-category="${it.category || ''}"> ${it.name} (hai ${it.qty}) — <input type="number" class="give-qty" min="1" max="${it.qty}" value="1" style="width:60px;"></label>
  `).join('') : '<p style="color:var(--gray);">Nessun oggetto.</p>';

  document.getElementById('generic-content').innerHTML = `
    <h3>🔄 Proponi Scambio</h3>
    <div class="form-section">
      <label>Con chi?</label>
      <select id="trade-target" onchange="renderTradeWant()">${targetOptions}</select>
    </div>
    <div class="form-section">
      <h4>Offri (tu → loro)</h4>
      ${giveItemsHtml}
      <label>Oro offerto: <input type="number" id="give-gp" min="0" value="0" style="width:90px;"></label>
    </div>
    <div class="form-section">
      <h4>Richiedi (loro → tu)</h4>
      <div id="trade-want-items"></div>
      <label>Oro richiesto: <input type="number" id="want-gp" min="0" value="0" style="width:90px;"></label>
    </div>
    <div class="modal-buttons">
      <button class="btn-success" onclick="submitTrade()">Invia Proposta</button>
      <button onclick="hideGeneric()">Annulla</button>
    </div>
  `;
  renderTradeWant();
  showElement('generic-modal');
};

window.renderTradeWant = function() {
  const ctx = window._tradeCtx;
  if (!ctx) return;
  const uid = document.getElementById('trade-target').value;
  const char = ctx.charByUser[uid];
  const items = char.items || [];
  document.getElementById('trade-want-items').innerHTML = items.length ? items.map(it => `
    <label style="display:block; margin:0.25rem 0;"><input type="checkbox" class="want-item" data-name="${it.name}" data-value="${it.value || 0}" data-category="${it.category || ''}"> ${it.name} (ha ${it.qty}) — <input type="number" class="want-qty" min="1" max="${it.qty}" value="1" style="width:60px;"></label>
  `).join('') : '<p style="color:var(--gray);">Nessun oggetto.</p>';
};

function collectCheckedItems(cbClass, qtyClass) {
  const out = [];
  document.querySelectorAll('.' + cbClass).forEach(cb => {
    if (cb.checked) {
      const qtyInput = cb.parentElement.querySelector('.' + qtyClass);
      out.push({ name: cb.dataset.name, qty: parseInt(qtyInput.value) || 1, value: parseInt(cb.dataset.value) || 0, category: cb.dataset.category || '' });
    }
  });
  return out;
}

window.submitTrade = async function() {
  const ctx = window._tradeCtx;
  if (!ctx) return;
  const uid = document.getElementById('trade-target').value;
  const targetChar = ctx.charByUser[uid];
  const giveItems = collectCheckedItems('give-item', 'give-qty');
  const wantItems = collectCheckedItems('want-item', 'want-qty');
  // Preserva i dettagli degli oggetti (dai personaggi di origine)
  const detailsFor = (list, name) => ((list || []).find(x => x.name === name) || {}).details || '';
  giveItems.forEach(it => { it.details = detailsFor(ctx.myChar.items, it.name); });
  wantItems.forEach(it => { it.details = detailsFor(targetChar.items, it.name); });
  const giveGp = parseInt(document.getElementById('give-gp').value) || 0;
  const wantGp = parseInt(document.getElementById('want-gp').value) || 0;
  if (giveItems.length === 0 && wantItems.length === 0 && giveGp === 0 && wantGp === 0) { alert('Proposta vuota.'); return; }
  try {
    showLoading();
    await addDoc(collection(db, 'trades'), {
      fromUserId: currentUser.uid, fromUserName: currentUsername, fromCharId: ctx.myChar.id, fromCharName: ctx.myChar.name,
      toUserId: uid, toUserName: ctx.users[uid] ? ctx.users[uid].username : '?', toCharId: targetChar.id, toCharName: targetChar.name,
      giveItems, giveGp, wantItems, wantGp, status: 'pending', createdAt: serverTimestamp()
    });
    hideLoading();
    hideGeneric();
    alert('Proposta di scambio inviata!');
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.acceptTrade = async function(tradeId) {
  const tref = doc(db, 'trades', tradeId);
  const tsnap = await getDoc(tref);
  if (!tsnap.exists()) return;
  const t = tsnap.data();
  if (t.toUserId !== currentUser.uid) { alert('Non sei il destinatario.'); return; }
  if (t.status !== 'pending') { alert('Scambio non più valido.'); loadMarket(); return; }
  if (!confirm('Accettare lo scambio?')) return;
  try {
    showLoading();
    const [fromSnap, toSnap] = await Promise.all([getDoc(doc(db, 'characters', t.fromCharId)), getDoc(doc(db, 'characters', t.toCharId))]);
    if (!fromSnap.exists() || !toSnap.exists()) throw new Error('personaggio mancante');
    const fromC = fromSnap.data();
    const toC = toSnap.data();
    let fromItems = fromC.items || [];
    let toItems = toC.items || [];
    let fromGp = fromC.gp || 0;
    let toGp = toC.gp || 0;
    if (fromGp < t.giveGp) throw new Error('oro insufficiente del proponente');
    if (toGp < t.wantGp) throw new Error('il tuo oro è insufficiente');
    for (const it of t.giveItems) { const r = removeItemFromList(fromItems, it.name, it.qty); if (!r) throw new Error(`il proponente non ha più ${it.name}`); fromItems = r; }
    for (const it of t.wantItems) { const r = removeItemFromList(toItems, it.name, it.qty); if (!r) throw new Error(`non hai più ${it.name}`); toItems = r; }
    for (const it of t.giveItems) toItems = addItemToList(toItems, it);
    for (const it of t.wantItems) fromItems = addItemToList(fromItems, it);
    fromGp = fromGp - t.giveGp + t.wantGp;
    toGp = toGp - t.wantGp + t.giveGp;
    const batch = writeBatch(db);
    batch.update(doc(db, 'characters', t.fromCharId), { items: fromItems, gp: fromGp });
    batch.update(doc(db, 'characters', t.toCharId), { items: toItems, gp: toGp });
    batch.update(tref, { status: 'accepted', resolvedAt: serverTimestamp() });
    await batch.commit();
    await notifyDM(`🔄 Scambio completato tra ${t.fromUserName} e ${t.toUserName}.`, 'trade');
    await logAudit({ charId: t.fromCharId, charName: t.fromCharName, action: 'trade', message: `Scambio con ${t.toUserName}`, gpDelta: (t.wantGp || 0) - (t.giveGp || 0) });
    await logAudit({ charId: t.toCharId, charName: t.toCharName, action: 'trade', message: `Scambio con ${t.fromUserName}`, gpDelta: (t.giveGp || 0) - (t.wantGp || 0) });
    hideLoading();
    alert('Scambio completato!');
    loadMarket();
  } catch (e) { hideLoading(); console.error(e); alert('Scambio fallito: ' + e.message); loadMarket(); }
};

window.rejectTrade = async function(tradeId) {
  if (!confirm('Rifiutare lo scambio?')) return;
  try { showLoading(); await updateDoc(doc(db, 'trades', tradeId), { status: 'rejected', resolvedAt: serverTimestamp() }); hideLoading(); loadMarket(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

window.cancelTrade = async function(tradeId) {
  if (!confirm('Annullare la proposta?')) return;
  try { showLoading(); await updateDoc(doc(db, 'trades', tradeId), { status: 'cancelled', resolvedAt: serverTimestamp() }); hideLoading(); loadMarket(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

// ===================================================================
// NOTIFICHE (DM e Player)
// ===================================================================
// Una notifica ha un'audience: 'dm' | 'players' | 'all'. Lo stato di lettura
// è per-utente (array readBy di uid).
function audienceMatches(audience) {
  const a = audience || 'dm';
  if (a === 'all') return true;
  return currentUserRole === 'dm' ? a === 'dm' : a === 'players';
}

async function notify(audience, message, type) {
  try {
    await addDoc(collection(db, 'notifications'), {
      audience, type: type || 'info', message,
      createdAt: serverTimestamp(), readBy: []
    });
  } catch (e) { console.error('Errore notifica:', e); }
}
async function notifyDM(message, type) { return notify('dm', message, type); }
async function notifyPlayers(message, type) { return notify('players', message, type); }

async function updateNotificationsCount() {
  if (!currentUser) return;
  try {
    const snap = await getDocs(collection(db, 'notifications'));
    let count = 0;
    snap.forEach(d => {
      const n = d.data();
      if (audienceMatches(n.audience) && !(n.readBy || []).includes(currentUser.uid)) count++;
    });
    const el = document.getElementById('notifications-count');
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  } catch (e) { console.error(e); }
}

async function loadNotifications() {
  const el = document.getElementById('notifications-list');
  el.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  try {
    const snap = await getDocs(collection(db, 'notifications'));
    notifications = [];
    snap.forEach(d => {
      const n = { id: d.id, ...d.data() };
      if (audienceMatches(n.audience)) notifications.push(n);
    });
    notifications.sort((a, b) => ((b.createdAt && b.createdAt.seconds) || 0) - ((a.createdAt && a.createdAt.seconds) || 0));

    if (notifications.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔔</div><p>Nessuna notifica.</p></div>';
      return;
    }

    el.innerHTML = `<div class="character-actions" style="margin-bottom:1rem;"><button class="btn-info" onclick="markAllNotificationsRead()">Segna tutte come lette</button></div>` +
      notifications.map(n => {
        const unread = !(n.readBy || []).includes(currentUser.uid);
        const date = n.createdAt ? new Date(n.createdAt.seconds * 1000).toLocaleString('it-IT') : '';
        return `<div class="card${unread ? ' pending-card' : ''}"><p>${n.message}</p><p style="color:var(--gray); font-size:0.85rem;">${date}${unread ? ' · <strong>NUOVA</strong>' : ''}</p></div>`;
      }).join('');
  } catch (e) {
    console.error(e);
    el.innerHTML = '<div class="empty-state"><p>Errore nel caricamento</p></div>';
  }
}

window.markAllNotificationsRead = async function() {
  try {
    showLoading();
    const batch = writeBatch(db);
    notifications.filter(n => !(n.readBy || []).includes(currentUser.uid))
      .forEach(n => batch.update(doc(db, 'notifications', n.id), { readBy: arrayUnion(currentUser.uid) }));
    await batch.commit();
    hideLoading();
    loadNotifications();
    updateNotificationsCount();
  } catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

// ===================================================================
// REGISTRO / AUDIT LOG
// ===================================================================
// Traccia ogni movimento di oro/oggetti/caratteristiche fatto TRAMITE l'app.
// Serve al DM come libro mastro: se il valore di un pg non torna con la somma
// dei movimenti registrati, qualcosa è stato aggirato fuori dall'app.
async function logAudit(entry) {
  try {
    await addDoc(collection(db, 'audit_log'), {
      charId: entry.charId || '',
      charName: entry.charName || '',
      actorId: currentUser.uid,
      actorName: currentUsername,
      action: entry.action || 'update',
      message: entry.message || '',
      gpDelta: entry.gpDelta || 0,
      createdAt: serverTimestamp()
    });
  } catch (e) { console.error('Errore audit:', e); }
}

async function loadAudit() {
  const el = document.getElementById('audit-list');
  if (currentUserRole !== 'dm') {
    el.innerHTML = '<div class="empty-state"><p>Sezione riservata al Dungeon Master.</p></div>';
    return;
  }
  el.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  try {
    const snap = await getDocs(collection(db, 'audit_log'));
    const entries = [];
    snap.forEach(d => entries.push({ id: d.id, ...d.data() }));
    entries.sort((a, b) => ((b.createdAt && b.createdAt.seconds) || 0) - ((a.createdAt && a.createdAt.seconds) || 0));

    if (entries.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📋</div><p>Nessun movimento registrato.</p></div>';
      return;
    }

    el.innerHTML = entries.slice(0, 200).map(e => {
      const date = e.createdAt ? new Date(e.createdAt.seconds * 1000).toLocaleString('it-IT') : '';
      const gp = e.gpDelta ? ` <strong style="color:${e.gpDelta >= 0 ? 'var(--success)' : 'var(--danger)'};">${e.gpDelta >= 0 ? '+' : ''}${e.gpDelta} gp</strong>` : '';
      return `<div class="card"><p><strong>${e.charName || '—'}</strong> · ${e.message}${gp}</p><p style="color:var(--gray); font-size:0.85rem;">da ${e.actorName} · ${date}</p></div>`;
    }).join('');
  } catch (err) {
    console.error(err);
    el.innerHTML = '<div class="empty-state"><p>Errore nel caricamento del registro</p></div>';
  }
}

// ===================================================================
// MAPPE
// ===================================================================
function fileToImage(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

// Comprime un'immagine sotto un budget di byte (limite documento Firestore ~1 MiB)
async function compressImage(file, budget = 900000) {
  const img = await fileToImage(file);
  const dims = [1600, 1300, 1000, 800];
  const quals = [0.82, 0.7, 0.6, 0.5, 0.4];
  for (const maxDim of dims) {
    const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
    const w = Math.max(1, Math.round(img.width * scale));
    const h = Math.max(1, Math.round(img.height * scale));
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
    for (const q of quals) {
      const url = canvas.toDataURL('image/jpeg', q);
      if (url.length <= budget) return url;
    }
  }
  return null;
}

async function loadMaps() {
  const el = document.getElementById('maps-content');
  el.innerHTML = '<div class="loading"><div class="dice-spinner">🎲</div></div>';
  try {
    const snap = await getDocs(collection(db, 'maps'));
    const maps = [];
    snap.forEach(d => maps.push({ id: d.id, ...d.data() }));
    maps.sort((a, b) => ((b.createdAt && b.createdAt.seconds) || 0) - ((a.createdAt && a.createdAt.seconds) || 0));
    window._maps = maps;
    renderMaps(maps);
  } catch (e) {
    console.error('Errore mappe:', e);
    el.innerHTML = '<div class="empty-state"><p>Errore nel caricamento delle mappe</p></div>';
  }
}

function renderMaps(maps) {
  const el = document.getElementById('maps-content');

  const dmControl = currentUserRole === 'dm' ? `
    <div class="profile-box" style="margin-bottom:1rem; text-align:left;">
      <label>Titolo mappa</label>
      <input type="text" id="map-title-input" placeholder="Es. Città di Neverwinter" />
      <div class="character-actions" style="margin-top:0.5rem;">
        <button class="btn-info btn-lg" onclick="triggerMapUpload()">📤 Carica Mappa (JPG/PNG)</button>
      </div>
    </div>` : '';

  const grid = maps.length === 0
    ? '<div class="empty-state"><div class="empty-state-icon">🗺️</div><p>Nessuna mappa disponibile.</p></div>'
    : `<div class="cards-grid">${maps.map(m => `
        <div class="card" style="cursor:pointer;" onclick="viewMap('${m.id}')">
          <img src="${m.image}" alt="${m.title}" class="map-thumb" />
          <h3>${m.title}</h3>
          <p style="color: var(--gray); font-size: 0.85rem;">Caricata da ${m.uploadedByName || '—'}</p>
          ${currentUserRole === 'dm' ? `<button class="btn-danger" onclick="event.stopPropagation(); deleteMap('${m.id}')">🗑️ Elimina</button>` : ''}
        </div>
      `).join('')}</div>`;

  el.innerHTML = dmControl + grid;
}

window.triggerMapUpload = function() {
  const title = (document.getElementById('map-title-input').value || '').trim();
  if (!title) { alert('Inserisci un titolo per la mappa.'); return; }
  window._pendingMapTitle = title;
  document.getElementById('map-file-input').click();
};

window.handleMapUpload = async function(event) {
  const file = event.target.files && event.target.files[0];
  event.target.value = '';
  if (!file) return;
  if (currentUserRole !== 'dm') { alert('Solo i DM possono caricare mappe.'); return; }
  const title = window._pendingMapTitle || 'Mappa';
  try {
    showLoading();
    const image = await compressImage(file, 900000);
    if (!image) {
      hideLoading();
      alert('Immagine troppo grande anche dopo la compressione. Usa una risoluzione più bassa.');
      return;
    }
    await addDoc(collection(db, 'maps'), {
      title,
      image,
      uploadedBy: currentUser.uid,
      uploadedByName: currentUsername,
      createdAt: serverTimestamp()
    });
    hideLoading();
    loadMaps();
  } catch (e) {
    hideLoading();
    console.error('Errore caricamento mappa:', e);
    alert('Errore nel caricamento della mappa');
  }
};

window.viewMap = function(mapId) {
  const map = (window._maps || []).find(m => m.id === mapId);
  if (!map) return;
  document.getElementById('generic-content').innerHTML = `
    <h3>${map.title}</h3>
    <img src="${map.image}" alt="${map.title}" style="width:100%; height:auto; border-radius:8px;" />
    <div class="modal-buttons">
      <button onclick="hideGeneric()">Chiudi</button>
    </div>
  `;
  showElement('generic-modal');
};

window.deleteMap = async function(mapId) {
  if (currentUserRole !== 'dm') { alert('Solo i DM possono eliminare mappe.'); return; }
  if (!confirm('Eliminare questa mappa?')) return;
  try { showLoading(); await deleteDoc(doc(db, 'maps', mapId)); hideLoading(); loadMaps(); }
  catch (e) { hideLoading(); console.error(e); alert('Errore'); }
};

// === FUNZIONE AGGIUNGI RIGA ARMA ===
window.addWeaponRow = function() {
  const container = document.getElementById('weapons-container');
  const newRow = document.createElement('div');
  newRow.className = 'weapon-row';
  newRow.innerHTML = `
    <input type="text" placeholder="Nome arma" class="weapon-name" />
    <select class="weapon-stat">
      <option value="">Caratteristica</option>
      <option value="str">FOR</option>
      <option value="dex">DES</option>
      <option value="cha">CAR</option>
    </select>
    <select class="weapon-bonus">
      <option value="0">+0</option>
      <option value="1">+1</option>
      <option value="2">+2</option>
      <option value="3">+3</option>
    </select>
    <input type="text" placeholder="Danni (es. 1d8+3)" class="weapon-damage" />
  `;
  container.appendChild(newRow);
};

// === FUNZIONI INCANTESIMI DINAMICI ===
window.addCantripRow = function() {
  const container = document.getElementById('cantrips-container');
  const newInput = document.createElement('input');
  newInput.type = 'text';
  newInput.placeholder = 'Nome trucchetto';
  newInput.className = 'cantrip-input';
  newInput.style.width = '100%';
  newInput.style.marginBottom = '0.5rem';
  container.appendChild(newInput);
};

let spellLevelCounter = 0;

window.addSpellLevel = function() {
  const container = document.getElementById('spell-levels-container');
  const existingLevels = container.querySelectorAll('.spell-level-section');
  const nextLevel = existingLevels.length + 1;
  
  if (nextLevel > 9) {
    alert('Puoi aggiungere massimo 9 livelli di incantesimi!');
    return;
  }
  
  spellLevelCounter++;
  const sectionId = `spell-level-${spellLevelCounter}`;
  
  const newSection = document.createElement('div');
  newSection.className = 'spell-level-section';
  newSection.id = sectionId;
  newSection.dataset.level = nextLevel;
  newSection.innerHTML = `
    <div class="spell-level-header">
      <h5>Livello ${nextLevel}</h5>
      <div>
        <label style="color: var(--gray); font-size: 0.9rem; margin-right: 0.5rem;">Slot:</label>
        <input type="number" class="slots-input" min="0" value="0" />
        <button type="button" onclick="removeSpellLevel('${sectionId}')" class="remove-spell-level" style="margin-left: 1rem;">Rimuovi</button>
      </div>
    </div>
    <div class="spells-list">
      <input type="text" placeholder="Nome incantesimo" class="spell-input" />
    </div>
    <button type="button" onclick="addSpellToLevel('${sectionId}')" class="btn-secondary" style="font-size: 0.85rem;">+ Aggiungi Incantesimo</button>
  `;
  
  container.appendChild(newSection);
};

window.addSpellToLevel = function(sectionId) {
  const section = document.getElementById(sectionId);
  const spellsList = section.querySelector('.spells-list');
  
  const newInput = document.createElement('input');
  newInput.type = 'text';
  newInput.placeholder = 'Nome incantesimo';
  newInput.className = 'spell-input';
  spellsList.appendChild(newInput);
};

window.removeSpellLevel = function(sectionId) {
  const section = document.getElementById(sectionId);
  if (confirm('Rimuovere questo livello di incantesimi?')) {
    section.remove();
    // Rinumera i livelli rimanenti
    renumberSpellLevels();
  }
};

function renumberSpellLevels() {
  const container = document.getElementById('spell-levels-container');
  const sections = container.querySelectorAll('.spell-level-section');
  sections.forEach((section, index) => {
    const level = index + 1;
    section.dataset.level = level;
    section.querySelector('h5').textContent = `Livello ${level}`;
  });
}

// === FUNZIONE EXPORT PDF ===
window.exportToPDF = async function(charId) {
  const char = currentCharacters.find(c => c.id === charId);
  if (!char) {
    alert('Personaggio non trovato');
    return;
  }
  
  try {
    await exportCharacterToPDF(char);
  } catch (error) {
    console.error('Errore export PDF:', error);
    alert('Errore durante l\'export PDF. Verifica la console per i dettagli.');
  }
};

// === SERVICE WORKER UPDATE ORCHESTRATOR ===
if ('serviceWorker' in navigator) {
  // Register service worker
  navigator.serviceWorker.register('/service-worker.js');
  
  // Reference to waiting worker
  let waitingWorker = null;
  
  // Reload on controller change (atomic switch point)
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    window.location.reload();
  });
  
  // Monitor for updates
  navigator.serviceWorker.ready.then(registration => {
    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing;
      waitingWorker = newWorker;
      
      newWorker.addEventListener('statechange', () => {
        // Show update button only if new SW is installed and we have an active controller
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          showUpdateButton(() => {
            waitingWorker.postMessage('SKIP_WAITING');
          });
        }
      });
    });
  });
}

function showUpdateButton(onUpdate) {
  // Prevent duplicate buttons
  if (document.getElementById('sw-update-btn')) return;
  
  const updateBanner = document.createElement('div');
  updateBanner.id = 'sw-update-banner';
  updateBanner.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--red);
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 10000;
    display: flex;
    gap: 1rem;
    align-items: center;
    animation: slideUp 0.3s ease;
  `;
  
  updateBanner.innerHTML = `
    <span>🎲 Nuovo aggiornamento disponibile!</span>
    <button id="sw-update-btn" style="
      background: white;
      color: var(--red);
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    ">Aggiorna Ora</button>
  `;
  
  document.body.appendChild(updateBanner);
  
  document.getElementById('sw-update-btn').addEventListener('click', () => {
    onUpdate();
    updateBanner.innerHTML = '<span>⏳ Aggiornamento in corso...</span>';
  });
}

// === INITIALIZE ===
document.addEventListener('DOMContentLoaded', () => {
  showLoading();
});