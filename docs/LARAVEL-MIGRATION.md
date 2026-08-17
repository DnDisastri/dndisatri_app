# Migrazione a Laravel

## Stato attuale

La repository `dndisatri_app` contiene la versione legacy dell'applicazione DnDisastri.

Stack legacy:

- HTML
- CSS
- JavaScript
- Firebase
- Firestore

L'ultima versione della vecchia applicazione è conservata nel tag:

`legacy-firebase-final`

## Nuova applicazione

Lo sviluppo della nuova versione avviene separatamente nella repository:

`dndisastri-app-demo`

Stack della nuova applicazione:

- PHP >= 8.3
- Laravel 13
- Blade
- Livewire
- Filament 5
- Spatie Laravel Permission
- Spatie Laravel Activitylog
- Vite
- Tailwind CSS
- Database relazionale

## Database

Durante lo sviluppo la nuova applicazione utilizza SQLite.

Per l'ambiente di produzione è previsto l'utilizzo di MySQL.

## Hosting di produzione

La destinazione prevista per la nuova applicazione è:

- SupportHost
- Piano Condiviso 2
- Dominio principale del progetto

La configurazione definitiva dell'ambiente di produzione verrà effettuata prima del deploy.

## Strategia di migrazione

La nuova applicazione continuerà a essere sviluppata nella repository `dndisastri-app-demo` fino al completamento della versione da pubblicare.

Nel frattempo questa repository viene preparata per accogliere la nuova applicazione.

La versione Laravel non deve essere sviluppata parallelamente in entrambe le repository.

Quando la nuova applicazione sarà pronta:

1. verrà conservata definitivamente la versione legacy;
2. verrà trasferita la nuova applicazione Laravel nella repository ufficiale;
3. verrà mantenuta, per quanto possibile, la cronologia dei commit della nuova applicazione;
4. verrà configurato l'ambiente di staging;
5. verrà verificato il funzionamento su SupportHost;
6. verrà effettuato il passaggio del dominio alla nuova applicazione.

## Branch

- `main`: versione ufficiale stabile
- `migration/laravel`: preparazione della repository alla migrazione
- `legacy-firebase-final`: tag dell'ultima versione della vecchia applicazione

## Regola durante lo sviluppo

Fino al completamento della nuova applicazione:

- lo sviluppo Laravel continua esclusivamente in `dndisastri-app-demo`;
- `dndisatri_app` non riceve ancora il codice Laravel;
- `main` non viene modificata per la migrazione;
- i preparativi vengono effettuati su `migration/laravel`.