# Migrazione a Laravel

## Stato attuale

La riscrittura Laravel è stata trasferita nella repository ufficiale `dndisatri_app`.

La migrazione del codice è stata eseguita sul branch:

`migration/laravel`

La vecchia applicazione Firebase non è più presente nel working tree di questo branch, ma rimane interamente recuperabile dalla cronologia Git.

La fase successiva è la verifica completa della repository ufficiale e il deploy su un sottodominio di staging SupportHost.

## Versione legacy

La vecchia applicazione utilizzava:

* HTML
* CSS
* JavaScript
* Firebase
* Firestore
* Service Worker / PWA

L'ultima versione della vecchia applicazione è conservata nel tag:

`legacy-firebase-final`

Questo tag non deve essere eliminato.

## Versione Laravel

La nuova applicazione utilizza:

* PHP >= 8.3
* Laravel 13
* Blade
* Livewire
* Filament 5
* Spatie Laravel Permission
* Spatie Laravel Activitylog
* Tailwind CSS 4
* Vite 8
* Pest
* SQLite in locale
* MySQL negli ambienti online

## Repository sorgente della riscrittura

La nuova applicazione è stata sviluppata inizialmente nella repository:

`dndisastri-app-demo`

Lo snapshot scelto come sorgente della migrazione è identificato dal tag:

`laravel-migration-ready`

Quel tag rappresenta lo stato del codice utilizzato per ricostruire la versione Laravel nella repository ufficiale.

## Strategia utilizzata

La cronologia Git di `dndisastri-app-demo` non è stata importata nella repository ufficiale.

Il codice è stato trasferito per blocchi funzionali, creando nuovi commit nella repository ufficiale.

Questo permette alla cronologia di `dndisatri_app` di raccontare direttamente il passaggio:

```text
applicazione Firebase
        ↓
preparazione migrazione
        ↓
ritiro applicazione legacy
        ↓
fondamenta Laravel
        ↓
dominio D&D
        ↓
database e modelli
        ↓
autorizzazioni e logica applicativa
        ↓
interfaccia e pannello amministrativo
        ↓
test
        ↓
staging
        ↓
produzione
```

## File volutamente non importati dalla repository demo

Alcuni file presenti nello snapshot della repository demo non sono stati trasferiti intenzionalmente.

### `.claude/launch.json`

Configurazione locale di sviluppo non necessaria alla repository ufficiale.

### `.gitattributes`

La repository ufficiale possiede già una configurazione preparata per la migrazione.

### `.gitignore`

La repository ufficiale utilizza il proprio `.gitignore`, adattato alla storia Firebase e alla nuova struttura Laravel.

### `resources/views/welcome.blade.php`

View standard Laravel non utilizzata dalle route dell'applicazione.

### `tools/convert-data.mjs`

Strumento utilizzato durante la conversione dei dati della vecchia PWA. Non fa parte del runtime della nuova applicazione.

### `README.md`

Il README della repository demo conteneva riferimenti alla fase di sviluppo e a documenti non trasferiti. È stato sostituito da documentazione specifica per la repository ufficiale.

## Database

### Locale

Lo sviluppo utilizza SQLite.

Il file:

```text
database/database.sqlite
```

è locale e non deve essere versionato.

### Staging e produzione

Gli ambienti online utilizzano MySQL.

Prima del deploy definitivo tutte le migration devono essere provate partendo da un database MySQL vuoto.

La versione migrata utilizza lo schema consolidato pre-lancio composto da 36 migration.

## Seeder

Il seeding standard comprende:

* `RoleSeeder`
* `MarketSeeder`

Questi dati sono necessari anche negli ambienti online.

In locale il `DatabaseSeeder` aggiunge anche dati utili allo sviluppo.

Per staging e produzione l'applicazione viene eseguita con:

```env
APP_ENV=production
```

in modo da utilizzare lo stesso comportamento della produzione ed evitare il caricamento dei dati di sviluppo.

Il primo amministratore non viene creato da un seeder.

Va creato con:

```bash
php artisan dndisastri:admin
```

## Staging

La prima pubblicazione avviene su un sottodominio dedicato di SupportHost.

Lo staging deve usare:

```env
APP_ENV=production
APP_DEBUG=false
```

con:

* `APP_URL` del sottodominio;
* database MySQL separato;
* `APP_KEY` propria;
* credenziali e configurazioni non versionate.

Lo staging serve a verificare l'applicazione nelle stesse condizioni operative della produzione senza modificare il dominio principale.

## Deploy di staging

Sequenza prevista:

```bash
composer install --no-dev --optimize-autoloader
php artisan filament:assets
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Creare quindi il primo amministratore:

```bash
php artisan dndisastri:admin
```

Gli asset frontend vengono compilati localmente:

```bash
npm run build
```

La directory:

```text
public/build/
```

non è versionata e deve quindi essere distribuita separatamente insieme alla release.

La document root del sottodominio deve puntare alla directory:

```text
public/
```

dell'applicazione Laravel.

## Verifiche prima dello staging

Prima del caricamento su SupportHost:

```bash
composer install
composer validate --strict
php artisan test
npm run build
```

Va inoltre verificato che:

* `.env` non sia versionato;
* `vendor/` non sia versionata;
* `node_modules/` non sia versionata;
* `database/database.sqlite` non sia versionato;
* `public/build/` non sia versionata;
* la working tree sia pulita;
* le migration funzionino su MySQL da zero.

## Test su staging

Prima di utilizzare il dominio principale vanno verificati almeno:

* landing pubblica;
* registrazione;
* login e logout;
* approvazione degli utenti;
* recupero password;
* personaggi;
* campagne;
* quest;
* serate;
* strumenti DM;
* mercato;
* annunci;
* scambi;
* richiami e vigilanza;
* notifiche;
* immagini e storage pubblico;
* pannello Filament;
* responsive desktop/mobile;
* pagine 403, 404 e 500;
* HTTPS;
* log Laravel;
* assenza di informazioni di debug.

## Passaggio a `main`

`main` non deve ricevere la nuova applicazione finché lo staging non è stato verificato.

Quando lo staging sarà stabile:

1. verificare che `migration/laravel` sia pulito e aggiornato;
2. eseguire nuovamente test e build;
3. integrare `migration/laravel` in `main`;
4. creare un tag della prima versione Laravel ufficiale;
5. distribuire la stessa versione sul dominio principale.

Il tag:

`legacy-firebase-final`

deve restare disponibile anche dopo il passaggio definitivo.

## Dopo la migrazione

Quando la versione Laravel sarà stabilmente in produzione:

* `dndisatri_app` diventerà l'unica repository di sviluppo attivo;
* `dndisastri-app-demo` potrà essere archiviata o mantenuta come riferimento storico;
* il branch `main` rappresenterà la versione Laravel ufficiale;
* lo sviluppo futuro seguirà il flusso branch → Pull Request → `main`;
* potranno essere aggiunti deploy automatici e controlli CI;
* andranno configurati backup periodici del database e dei file caricati.

## Prossimo passo

Lo stato attuale è:

```text
codice Laravel trasferito
        ↓
test trasferiti
        ↓
documentazione aggiornata
        ↓
verifica locale della repository ufficiale
        ↓
staging SupportHost
```

Il prossimo passaggio operativo è quindi verificare la repository ufficiale in locale, partendo dalle dipendenze e dal database, prima di creare il sottodominio di staging.
