{{--
    La manutenzione: `php artisan down`. È il gemello annunciato del 500 — la
    stessa schermata, ma per una fermata voluta — e senza questo file sarebbe
    l'unica pagina dell'applicazione a uscire ancora in inglese e senza tema.
--}}
@include('errors.pagina', [
    'codice' => 503,
    'titolo' => 'Torniamo subito',
    'testo' => 'La gilda è chiusa per qualche minuto: stiamo mettendo a posto.
                Riprova fra poco.',
])
