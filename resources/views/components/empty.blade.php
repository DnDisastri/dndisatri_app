@props(['size' => 'md'])

{{--
    «Ancora niente qui.»

    Era scritto a mano nove volte in sette file, e quattro di quelle nove
    avevano un'imbottitura diversa dalle altre senza nessuna ragione: una
    pagina vuota non la si guarda mai due volte di seguito, quindi le
    differenze non si notavano.

    Le due misure invece sono una distinzione vera e resta:

    - la predefinita è per **una sezione vuota** dentro una pagina che ha
      dell'altro. Sulla Home ce ne possono essere due di fila, e alte come la
      grande peserebbero più del contenuto vero;
    - `lg` è per **una pagina intera vuota** — nessuna campagna, nessun evento
      — dove il riquadro è l'unica cosa che c'è e deve reggere lo schermo.
--}}
{{-- `padding="none"`: l'imbottitura la decide la misura qui sotto, e passarla
     come classe sopra a quella della card le farebbe litigare in silenzio. --}}
<x-card padding="none"
        {{ $attributes->merge(['class' => 'block px-4 text-center text-sm text-muted '.($size === 'lg' ? 'py-8' : 'py-6')]) }}>
    {{ $slot }}
</x-card>
