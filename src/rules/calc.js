// === REGOLE DI CALCOLO (funzioni pure, estratte da app.js) ===
import { CLASSES, THIRD_CASTER_SUBCLASSES } from '../data/classes.js?v=37';
import { FULL_CASTER_SLOTS, HALF_CASTER_SLOTS, THIRD_CASTER_SLOTS, WARLOCK_SLOTS } from '../data/spells.js?v=37';
import { SKILLS_MAP } from '../data/character-basics.js?v=39';

export function calculateModifier(score) {
  return Math.floor((score - 10) / 2);
}

export function formatModifier(mod) {
  return mod >= 0 ? `+${mod}` : `${mod}`;
}

export function casterType(cls, subclass) {
  const c = CLASSES[cls];
  if (!c) return 'none';
  if (c.caster === 'none') return THIRD_CASTER_SUBCLASSES.includes(subclass) ? 'third' : 'none';
  return c.caster;
}

export function expertiseCountFor(cls, level) {
  level = level || 1;
  if (cls === 'Ladro') return level >= 6 ? 4 : 2;
  if (cls === 'Bardo') { if (level >= 10) return 4; if (level >= 3) return 2; return 0; }
  return 0;
}

export function subclassLevel(cls) {
  if (['Chierico', 'Stregone', 'Warlock'].includes(cls)) return 1;
  if (['Druido', 'Mago'].includes(cls)) return 2;
  return 3;
}

export function spellSlotsForCharacter(char) {
  const type = casterType(char.class, char.subclass);
  const level = char.level || 1;
  if (type === 'none') return null;
  if (type === 'pact') {
    const w = WARLOCK_SLOTS[level] || WARLOCK_SLOTS[1];
    return { pact: true, pactCount: w[0], pactLevel: w[1] };
  }
  const table = type === 'full' ? FULL_CASTER_SLOTS : type === 'half' ? HALF_CASTER_SLOTS : THIRD_CASTER_SLOTS;
  const arr = table[level];
  if (!arr) return { pact: false, slots: {} };
  const slots = {};
  arr.forEach((n, i) => { if (n > 0) slots[i + 1] = n; });
  return { pact: false, slots };
}

export function maxCastableSpellLevel(char) {
  const info = spellSlotsForCharacter(char);
  if (!info) return 0;
  if (info.pact) return info.pactLevel || 0;
  const keys = Object.keys(info.slots || {});
  return keys.length ? Math.max.apply(null, keys.map(Number)) : 0;
}

export function getProficiencyBonus(level) {
  if (level >= 17) return 6;
  if (level >= 13) return 5;
  if (level >= 9) return 4;
  if (level >= 5) return 3;
  return 2;
}

export function calculateSpellDC(spellAbility, profBonus, stats) {
  if (!spellAbility || !stats) return 0;
  const abilityMod = calculateModifier(stats[spellAbility] || 10);
  return 8 + abilityMod + profBonus;
}

export function calculateSpellAttackBonus(spellAbility, profBonus, stats) {
  if (!spellAbility || !stats) return 0;
  const abilityMod = calculateModifier(stats[spellAbility] || 10);
  return abilityMod + profBonus;
}

export function calculateWeaponAttackBonus(attackStat, weaponBonus, profBonus, stats) {
  if (!attackStat || !stats) return 0;
  const abilityMod = calculateModifier(stats[attackStat] || 10);
  return abilityMod + profBonus + (weaponBonus || 0);
}

export function calculateProficiencyBonus(level) {
  return Math.floor((level - 1) / 4) + 2;
}

export function calculateSkillBonus(stats, skill, proficient, expertise, proficiencyBonus) {
  const ability = SKILLS_MAP[skill];
  const abilityScore = stats[ability] || 10;
  const abilityMod = calculateModifier(abilityScore);
  
  let bonus = abilityMod;
  if (proficient) bonus += proficiencyBonus;
  if (expertise) bonus += proficiencyBonus;
  
  return bonus;
}

export function calculateSavingThrow(stats, ability, proficient, profBonus) {
  const abilityScore = stats[ability] || 10;
  const abilityMod = calculateModifier(abilityScore);
  return abilityMod + (proficient ? profBonus : 0);
}

