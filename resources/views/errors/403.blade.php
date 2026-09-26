@php
    /*
     * Il perché, quando c'è: `abort(403, 'Serve un personaggio vivo per usare
     * il mercato.')` scrive una frase che vale più di qualsiasi testo generico,
     * e va mostrata.
     *
     * Quello che non si mostra è il messaggio predefinito di Laravel — «This
     * action is unauthorized.» — che arriva da `authorize()` quando una policy
     * dice di no: è in inglese, e a chi guarda non dice niente che il titolo qui
     * sopra non dica già meglio.
     */
    $messaggio = trim(($exception ?? null)?->getMessage() ?? '');
    $dettaglio = $messaggio !== '' && ! str_starts_with($messaggio, 'This action') ? $messaggio : null;
@endphp

@include('errors.pagina', [
    'codice' => 403,
    'titolo' => 'Non hai i permessi',
    'testo' => 'Questa pagina è chiusa a chi non ha il permesso di aprirla.
                Se pensi che dovrebbe essere aperta a te, dillo a un dungeon master.',
    'dettaglio' => $dettaglio,
])
