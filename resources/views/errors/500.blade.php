{{--
    Il guasto. **Nessun dettaglio**: qui il messaggio dell'eccezione è quello di
    un errore vero — un nome di colonna, un percorso di file — e raccontarlo a
    chi passa non lo aiuta e dice a tutti com'è fatta l'applicazione dentro.
    Quello che serve a chi ripara sta nei log.
--}}
@include('errors.pagina', [
    'codice' => 500,
    'titolo' => 'Qualcosa è andato storto',
    'testo' => 'Non è colpa tua: l\'applicazione si è fermata a metà. Riprova
                fra un momento; se succede ancora, dillo a un admin.',
])
