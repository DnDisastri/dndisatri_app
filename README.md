# D&Disastri

Gestionale Laravel per D&Disastri, dedicato alla gestione di personaggi, campagne, quest, sessioni di gioco, mercato interno, scambi, contenuti della gilda e strumenti per Dungeon Master e amministratori.

L'applicazione è una riscrittura completa della precedente PWA basata su Firebase.

## Stack

* PHP >= 8.3
* Laravel 13
* Blade
* Livewire
* Filament 5
* Spatie Laravel Permission
* Spatie Laravel Activitylog
* Tailwind CSS 4
* Vite 8
* SQLite in sviluppo locale
* MySQL negli ambienti online
* Pest per i test

## Storia del progetto

La versione precedente basata su Firebase è conservata nel tag:

`legacy-firebase-final`

La riscrittura Laravel è stata sviluppata inizialmente nella repository separata:

`dndisastri-app-demo`

Lo snapshot scelto per la migrazione è identificato dal tag:

`laravel-migration-ready`

Il codice Laravel è stato successivamente ricostruito nella repository ufficiale attraverso nuovi commit organizzati per area funzionale, senza importare la cronologia Git della repository demo.

## Installazione locale

Installare le dipendenze PHP:

```bash
composer install
```

Installare le dipendenze frontend:

```bash
npm install
```

Creare il file di configurazione locale:

```bash
cp .env.example .env
```

Generare la chiave dell'applicazione:

```bash
php artisan key:generate
```

Lo sviluppo locale usa SQLite. Creare il database se non esiste:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Eseguire le migration:

```bash
php artisan migrate
```

Collegare lo storage pubblico:

```bash
php artisan storage:link
```

Compilare gli asset:

```bash
npm run build
```

Avviare l'applicazione:

```bash
php artisan serve
```

## Dati locali opzionali

Per creare ruoli, catalogo del mercato e dati utili allo sviluppo locale:

```bash
php artisan db:seed
```

I dati di sviluppo non devono essere utilizzati negli ambienti online.

Per creare manualmente un amministratore:

```bash
php artisan dndisastri:admin
```

Il comando richiede le credenziali direttamente nel terminale e non le salva nella repository.

## Test

Per eseguire la suite completa:

```bash
php artisan test
```

oppure:

```bash
composer test
```

Prima di distribuire una nuova versione devono passare almeno:

```bash
composer validate --strict
php artisan test
npm run build
```

## Dati D&D

Le regole e i dati di riferimento si trovano principalmente in:

```text
app/Domain/Dnd/
config/dnd/
```

Comprendono classi, sottoclassi, specie, background, equipaggiamento, incantesimi e regole utilizzate dalla scheda personaggio.

Le descrizioni presenti nel progetto sono contenuti sintetici/originali e non riproducono integralmente testo editoriale ufficiale.

## Pannello amministrativo

Il pannello Filament è disponibile su:

```text
/admin
```

L'accesso dipende dal ruolo e dalle policy dell'applicazione.

Il primo amministratore di una nuova installazione va creato da terminale:

```bash
php artisan dndisastri:admin
```

## Database

### Sviluppo

SQLite:

```env
DB_CONNECTION=sqlite
```

### Ambienti online

MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Le credenziali non devono essere versionate.

## Staging e produzione

La destinazione prevista è SupportHost, piano Condiviso 2.

Lo staging utilizza un database separato e un sottodominio dedicato, ma viene eseguito con comportamento da produzione:

```env
APP_ENV=production
APP_DEBUG=false
```

`APP_URL`, database e altre configurazioni devono invece riferirsi all'ambiente specifico.

Installazione delle dipendenze PHP sul server:

```bash
composer install --no-dev --optimize-autoloader
```

Pubblicazione degli asset Filament:

```bash
php artisan filament:assets
```

Migration:

```bash
php artisan migrate --force
```

Seeder di base:

```bash
php artisan db:seed --force
```

In ambiente `production` il seeding standard crea i ruoli e il catalogo iniziale del mercato senza caricare i dati di sviluppo.

Collegamento dello storage:

```bash
php artisan storage:link
```

Gli asset Vite vengono compilati localmente:

```bash
npm run build
```

La directory risultante:

```text
public/build/
```

deve essere distribuita sul server separatamente perché non è versionata nella repository.

## Cache Laravel

Dopo aver completato la configurazione dell'ambiente online è possibile utilizzare:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Dopo modifiche alla configurazione può essere utile azzerarle con:

```bash
php artisan optimize:clear
```

## Documentazione della migrazione

Lo stato e la strategia del passaggio dalla vecchia applicazione Firebase alla versione Laravel sono documentati in:

```text
docs/LARAVEL-MIGRATION.md
```
