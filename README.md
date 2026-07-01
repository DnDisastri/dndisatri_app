# D&Disastri - PWA per Gestione Personaggi D&D

## 🎲 Panoramica
Webapp MVP per gestire personaggi D&D, quest e memoriale degli eroi caduti.

## 📋 Prerequisiti Completati
- ✅ Firebase Project creato (`dndisastri-app`)
- ✅ Firebase Authentication abilitato (Email/Password)
- ✅ Firestore Database creato (Standard Edition)
- ✅ Firebase Hosting configurato

## 🚀 Deploy Immediato

### 1. Configurazione Firestore Rules
Prima di fare il deploy, devi impostare le regole di sicurezza:

1. Vai su [Firebase Console](https://console.firebase.google.com/project/dndisastri-app/firestore)
2. Clicca su "Rules" (Regole)
3. Copia e incolla il contenuto di `firestore.rules`
4. Clicca "Publish" (Pubblica)

### 2. Deploy su Firebase Hosting

```bash
# Assicurati di essere nella directory del progetto
cd /path/to/dndisastri

# Deploy
firebase deploy --only hosting:dndisastri-app
```

L'app sarà disponibile su: `https://dndisastri-app.web.app`

### 3. Configurare il primo Dungeon Master

Dopo il primo deploy:

1. Registra il primo account (questo sarà il DM)
2. Vai su [Firestore Console](https://console.firebase.google.com/project/dndisastri-app/firestore/data)
3. Trova il documento dell'utente nella collection `users`
4. Modifica il campo `role` da `player` a `dm`
5. Salva

Ora quel utente può:
- Creare quest
- Dichiarare morti i personaggi

**IMPORTANTE:** Solo i DM possono creare quest e dichiarare morti i personaggi!

## 📱 Installazione come App

### Android
1. Apri `https://dndisastri-app.web.app` in Chrome
2. Tocca il menu (⋮) → "Aggiungi a schermata Home"
3. L'app si aprirà in fullscreen come app nativa

### iOS
1. Apri `https://dndisastri-app.web.app` in Safari
2. Tocca il pulsante "Condividi" (icona con freccia)
3. Scorri e tocca "Aggiungi a schermata Home"

## 🎨 Design & Colori
Colori dal logo D&Disastri:
- **Navy Blue**: `#2c3e6e` (primario)
- **Rosso**: `#d4423e` (accenti)
- **Beige**: `#e8d5a0` (testo)
- **Dark**: `#111111` (sfondo)

## 🏗️ Struttura del Progetto

```
dndisastri-app/
├── index.html          # Struttura HTML
├── style.css           # Stili con colori dal logo
├── app.js              # Logica + Firebase integration
├── manifest.json       # PWA manifest
├── service-worker.js   # Service worker per offline
├── logo.jpg            # Logo dell'app
├── firestore.rules     # Regole di sicurezza Firestore
├── firebase.json       # Configurazione hosting
└── .firebaserc         # Configurazione progetto
```

## 📊 Database Structure

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

### Collection: `characters`
```javascript
{
  userId: string,        // uid del proprietario
  name: string,
  class: string,
  level: number,
  race: string,
  notes: string,
  isDead: boolean,
  createdAt: timestamp,
  deathDate: timestamp   // solo se isDead = true
}
```

### Collection: `quests`
```javascript
{
  createdBy: string,     // uid del creatore
  title: string,
  description: string,
  difficulty: string,    // Facile, Media, Difficile, Epica
  isCompleted: boolean,
  createdAt: timestamp
}
```

## 🔐 Sicurezza e Regole

### Ruoli Utente
- **Player (default)**: 
  - Può creare UN SOLO personaggio attivo
  - Può visualizzare tutte le quest
  - Può visualizzare tutti i personaggi (per Hall of Fallen Heroes)
  - NON può creare quest
  - NON può dichiarare morti i personaggi

- **Dungeon Master (DM)**:
  - Può creare quest illimitate
  - Può dichiarare morti QUALSIASI personaggio
  - Ha controllo completo sulla campagna

### Regole Firestore
- Authentication richiesta per tutte le operazioni
- Un player può avere solo UN personaggio attivo alla volta
- Solo i DM possono modificare il campo `isDead` dei personaggi
- Solo i DM possono creare/modificare/eliminare quest
- Tutti possono leggere tutti i personaggi (per Hall of Fallen Heroes)

## ✨ Funzionalità

### Autenticazione
- [x] Registrazione con email/password
- [x] Login
- [x] Logout
- [x] Username personalizzato

### Personaggi
- [x] Creazione personaggio
- [x] Lista personaggi vivi
- [x] Dettaglio personaggio
- [x] Dichiarazione morte (irreversibile)
- [x] Vista sola lettura per personaggi morti

### Quest
- [x] Creazione quest
- [x] Lista quest
- [x] Badge difficoltà

### Hall of Fallen Heroes
- [x] Vista globale personaggi morti
- [x] Memoriale pubblico
- [x] Ordinamento per data di morte

## 🛠️ Tecnologie
- **Frontend**: HTML, CSS, Vanilla JavaScript
- **Backend**: Firebase (Auth + Firestore)
- **Hosting**: Firebase Hosting
- **PWA**: Service Worker, Web Manifest

## 📈 Prossimi Step (Post-MVP)
- [ ] Modifica personaggi esistenti
- [ ] Completamento quest
- [ ] Gestione XP e progressione
- [ ] Upload avatar personaggi
- [ ] Sistema di notifiche
- [ ] Statistiche personaggi
- [ ] Export/Import personaggi
- [ ] Ricerca e filtri

## 🐛 Troubleshooting

### "Firebase not defined"
Assicurati di avere una connessione internet - i moduli Firebase vengono caricati da CDN.

### "Permission denied"
Verifica che le regole Firestore siano state pubblicate correttamente.

### "Authentication error"
Controlla che Authentication sia abilitato nella Firebase Console.

### App non si installa
- Android: usa Chrome (non altri browser)
- iOS: usa Safari (non Chrome)

## 💰 Costi
**Zero costi** con i limiti gratuiti:
- Authentication: illimitato
- Firestore: 50K letture/giorno, 20K scritture/giorno
- Hosting: 10GB storage, 360MB/giorno bandwidth

## 📝 Note di Sviluppo
- Nessun framework necessario (intenzionale)
- Codice mantenibile e leggibile
- Deploy rapido (< 1 minuto)
- Facilmente estendibile

## 🎯 Obiettivo MVP
✅ App funzionante subito
✅ Installabile su mobile
✅ Gestione personaggi completa
✅ Sistema quest base
✅ Memoriale fallen heroes

## 📞 Contatti
Per domande o supporto sul progetto D&Disastri.

---

**Versione**: 1.0.0 (MVP)  
**Ultimo aggiornamento**: Febbraio 2026
