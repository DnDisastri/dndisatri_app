# D&Disastri v2.0 - PWA Completa per D&D

## 🎲 Panoramica Aggiornamenti

### ✅ Nuove Funzionalità Implementate

#### 1. Logo Corretto
- Logo ora visibile in header e manifest PWA
- Path corretto: `/logo.jpg`
- Dimensioni ottimizzate per display mobile

#### 2. Scheda Personaggio Estesa
**Nuovi campi implementati:**
- ✅ Punteggi caratteristiche: STR, DEX, CON, INT, WIS, CHA
- ✅ Modificatori automatici calcolati
- ✅ Sottoclasse
- ✅ Background
- ✅ Classe Armatura (AC)
- ✅ Iniziativa
- ✅ Velocità (ft)
- ✅ Punti Ferita Massimi
- ✅ Abilità di Classe (testo esteso)
- ✅ Abilità di Sottoclasse (testo esteso)
- ✅ Inventario (lista/testo)
- ✅ Spellcasting completo:
  - Caratteristica incantatore
  - Lista incantesimi (trucchetti + livelli)

**Visualizzazione scheda:**
- Box caratteristiche con modificatori
- Layout organizzato per sezioni
- Form modale esteso per creazione/modifica

#### 3. Sistema di Modifiche Pending
**Flusso completo:**
- Players propongono modifiche ai propri personaggi
- Modifiche create come documenti in `pending_changes`
- DM riceve notifica (badge con contatore)
- DM può visualizzare diff delle modifiche
- DM approva o rifiuta
- Approvazione → aggiornamento automatico personaggio

**Regola speciale livello:**
- Livello modificabile SOLO da DM direttamente
- Players non possono cambiare livello (nemmeno con pending)

#### 4. Quest con Slot Partecipanti
**Funzionalità:**
- Numero massimo partecipanti definito alla creazione
- Lista partecipanti dinamica
- Players possono iscriversi/abbandonare
- Visualizzazione slot disponibili in real-time
- Indicatore "Completa" quando piena

#### 5. Sezione Pending (Solo DM)
- Nuova sezione dashboard per DM
- Lista tutte le modifiche in attesa
- Visualizzazione diff prima/dopo
- Azioni rapide: Approva/Rifiuta

## 🗂️ Struttura Database Firestore

### Collection: `users`
```javascript
{
  uid: string,
  username: string,
  email: string,
  role: string,          // 'player' or 'dm'
  createdAt: timestamp
}
```

### Collection: `characters` (AGGIORNATA)
```javascript
{
  userId: string,
  name: string,
  class: string,
  subclass: string,
  race: string,
  background: string,
  level: number,         // Default 1, solo DM può modificare
  
  stats: {               // NUOVO
    str: number,
    dex: number,
    con: number,
    int: number,
    wis: number,
    cha: number
  },
  
  combat: {              // NUOVO
    ac: number,
    initiative: number,
    speed: number,
    hpMax: number
  },
  
  classFeatures: string,      // NUOVO
  subclassFeatures: string,   // NUOVO
  inventory: string,          // NUOVO
  spellAbility: string,       // NUOVO
  spells: string,             // NUOVO
  notes: string,
  
  isDead: boolean,
  createdAt: timestamp,
  deathDate: timestamp   // solo se isDead = true
}
```

### Collection: `pending_changes` (NUOVA)
```javascript
{
  type: string,              // 'character_edit'
  characterId: string,       // ID del personaggio
  characterName: string,     // Nome per display
  requestedBy: string,       // UID del player
  requestedByName: string,   // Username per display
  
  oldData: object,           // Dati prima della modifica
  newData: object,           // Dati proposti
  
  status: string,            // 'pending', 'approved', 'rejected'
  createdAt: timestamp,
  
  // Compilati quando status cambia
  approvedBy: string,        // UID del DM (opzionale)
  approvedAt: timestamp,     // (opzionale)
  rejectedBy: string,        // UID del DM (opzionale)
  rejectedAt: timestamp      // (opzionale)
}
```

### Collection: `quests` (AGGIORNATA)
```javascript
{
  createdBy: string,
  title: string,
  description: string,
  difficulty: string,
  
  maxParticipants: number,   // NUOVO
  participants: [            // NUOVO
    {
      userId: string,
      username: string
    }
  ],
  
  isCompleted: boolean,
  createdAt: timestamp
}
```

## 🚀 Istruzioni Deploy

### 1. Backup del vecchio progetto
```bash
# Fai backup della directory attuale (opzionale)
cp -r /path/to/dndisastri /path/to/dndisastri-backup
```

### 2. Sostituisci i file
Copia i nuovi file nella directory del progetto:
- `index.html` (v3)
- `style.css` (v3)
- `app.js` (v3)
- `firestore.rules` (aggiornate)
- `manifest.json` (invariato, già corretto)
- `service-worker.js` (invariato)

### 3. Aggiorna Firestore Rules
**IMPORTANTE:** Prima di deployare hosting, aggiorna le regole!

```bash
# Deploy solo le regole
firebase deploy --only firestore:rules
```

Oppure manualmente:
1. Vai su [Firebase Console](https://console.firebase.google.com/project/dndisastri-app/firestore/rules)
2. Copia il contenuto di `firestore.rules`
3. Incolla e clicca "Pubblica"

### 4. Deploy Hosting
```bash
# Deploy hosting con nuova versione
firebase deploy --only hosting:dndisastri-app
```

### 5. Clear Cache Utenti
**FONDAMENTALE:** Gli utenti devono fare hard refresh per vedere i cambiamenti!

Comunica agli utenti:
- **Windows/Linux**: `Ctrl + Shift + R`
- **Mac**: `Cmd + Shift + R`
- Oppure: F12 → Application → Clear Storage → Clear site data

### 6. Imposta il primo DM (se non già fatto)
1. Vai su [Firestore Console](https://console.firebase.google.com/project/dndisastri-app/firestore/data)
2. Collection `users`
3. Trova il documento dell'utente
4. Modifica campo `role` da `player` a `dm`
5. Salva

## 🎯 Guida Utilizzo

### Per i Players

#### Creare un Personaggio
1. Dashboard → "I miei Personaggi"
2. Clicca "+ Nuovo"
3. Compila tutti i campi:
   - Informazioni base
   - Caratteristiche (default 10)
   - Statistiche combattimento
   - Abilità di classe/sottoclasse
   - Inventario
   - Incantesimi (se caster)
4. Crea Personaggio

**Nota:** Puoi avere solo UN personaggio attivo alla volta!

#### Modificare un Personaggio
1. Apri dettaglio personaggio
2. Clicca "Modifica"
3. Modifica i campi desiderati
4. Clicca "Proponi Modifiche"
5. Attendi approvazione DM

**Nota:** 
- Il livello NON può essere modificato dai player
- Tutte le modifiche richiedono approvazione DM

#### Iscriversi a una Quest
1. Dashboard → "Quest"
2. Clicca su una quest
3. Verifica slot disponibili
4. Clicca "Iscriviti"

### Per i Dungeon Master

#### Approvare Modifiche
1. Dashboard → "Modifiche Pending" (badge con numero)
2. Clicca su richiesta
3. Visualizza diff modifiche (rosso = vecchio, verde = nuovo)
4. Approva o Rifiuta

#### Modificare Livello Personaggio
1. Apri dettaglio personaggio
2. Clicca "Modifica Livello"
3. Inserisci nuovo livello (1-20)
4. Conferma

#### Dichiarare Morto un Personaggio
1. Apri dettaglio personaggio
2. Clicca "Dichiara Morto"
3. Conferma (azione irreversibile!)
4. Personaggio passa in "Fallen Heroes"

#### Creare una Quest
1. Dashboard → "Quest"
2. Clicca "+ Nuova"
3. Compila:
   - Titolo
   - Descrizione
   - Difficoltà
   - Numero massimo partecipanti
4. Crea Quest

## 🔒 Sicurezza

### Regole Principali
- ✅ Players possono creare solo 1 personaggio attivo
- ✅ Livello sempre inizia a 1
- ✅ Solo DM può modificare livello direttamente
- ✅ Solo DM può dichiarare morti i personaggi
- ✅ Solo DM può creare quest
- ✅ Players possono solo iscriversi/abbandonare quest
- ✅ Tutte le modifiche players passano da pending_changes
- ✅ Solo DM può approvare/rifiutare modifiche
- ✅ Tutti possono vedere Fallen Heroes (pubblico)

## 📱 PWA Features

### Installazione
- **Android**: Chrome → Menu → "Aggiungi a schermata Home"
- **iOS**: Safari → Condividi → "Aggiungi a schermata Home"

### Funzionalità Offline
- Cache statica: HTML, CSS, JS, logo
- Service worker con strategia cache-first
- Aggiornamento automatico versione

## 🎨 UI/UX

### Colori Tema
- Navy Blue (#2c3e6e): Primario
- Rosso (#d4423e): Accenti/azioni
- Beige (#e8d5a0): Testo
- Dark (#1a1a1a, #111111): Sfondi

### Layout Responsivo
- Desktop: Grid multi-colonna
- Mobile: Colonna singola
- Modali: Scrollabili con max-height
- Form estesi: Sezioni organizzate

### Icone & Emoji
- ⚔️ Personaggi
- 📜 Quest
- ☠️ Fallen Heroes
- ⏳ Modifiche Pending
- 🎲 Loading
- 💀 Morte

## 🐛 Troubleshooting

### "Permission denied" in Firestore
→ Verifica che le nuove regole siano pubblicate

### Modifiche non visibili dopo deploy
→ Hard refresh browser (Ctrl+Shift+R)

### Logo non appare
→ Verifica che `logo.jpg` sia nella root del progetto

### Service Worker problemi
→ F12 → Application → Service Workers → Unregister → Ricarica

### Pending changes non compare per DM
→ Verifica campo `role` in collection `users`

### Player può modificare livello
→ Le vecchie regole non sono state aggiornate!

## 📊 Limiti Gratuiti Firebase

**Firestore:**
- 50,000 letture/giorno ✅
- 20,000 scritture/giorno ✅
- 20,000 delete/giorno ✅
- 1 GiB storage ✅

**Hosting:**
- 10 GB storage ✅
- 360 MB/giorno bandwidth ✅

**Con le nuove funzioni:**
- Pending changes aggiunge scritture
- Contatori real-time per DM
- Più query per visualizzazioni estese

**Stima utilizzo:**
- ~10 players attivi: OK
- ~50 pending changes/mese: OK
- ~100 quest attive: OK

## 📝 Changelog v2.0

### Added
- Scheda personaggio estesa con tutti i campi D&D
- Sistema pending changes per modifiche players
- Sezione pending per DM con diff viewer
- Quest con slot partecipanti limitati
- Iscrizione/abbandono quest
- Modifica livello diretta per DM
- Badge notifiche per DM
- Calcolo automatico modificatori caratteristiche
- Form organizzati per sezioni
- Visualizzazione scheda completa con stat boxes

### Changed
- Struttura database characters completamente rinnovata
- Firestore rules aggiornate con nuove logiche
- UI scheda personaggio completamente ridisegnata
- Modal più grandi per form estesi
- CSS con nuove classi per layout complessi

### Fixed
- Logo ora visibile e corretto
- Path CSS/JS con versioning
- Service worker cache aggiornata

## 🔮 Roadmap Futura (Post v2.0)

- [ ] HP correnti tracciabili in combat
- [ ] Gestione incantesimi con slot disponibili
- [ ] Sistema XP e progressione automatica
- [ ] Upload immagini personaggi
- [ ] Chat in-app per quest
- [ ] Dadi virtuali con animazioni
- [ ] Export PDF scheda personaggio
- [ ] Statistiche campagna per DM
- [ ] Backup/restore personaggi
- [ ] Notifiche push per approvazioni
- [ ] Timeline eventi campagna

## 👥 Supporto

Per problemi o domande:
1. Controlla questa guida
2. Verifica console browser (F12)
3. Controlla Firestore rules
4. Fai hard refresh

---

**Versione**: 2.0.0  
**Data Release**: Febbraio 2026  
**Compatibilità**: Chrome, Safari, Firefox, Edge  
**Requisiti**: Firebase Project attivo, Authentication abilitato, Firestore abilitato
