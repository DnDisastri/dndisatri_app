@props(['size' => 'md'])

{{--
    «Ancora niente qui.»

    Due misure: la predefinita per una sezione vuota dentro una pagina con
    dell'altro (sulla Home ce ne possono essere due di fila); `lg` per una
    pagina intera vuota, dove il riquadro deve reggere lo schermo.
--}}
{{-- `padding="none"`: l'imbottitura la decide la misura qui sotto, e passarla
     come classe sopra a quella della card le farebbe litigare in silenzio. --}}
<x-card padding="none"
        {{ $attributes->merge(['class' => 'block px-4 text-center text-sm text-muted '.($size === 'lg' ? 'py-8' : 'py-6')]) }}>
    {{ $slot }}
</x-card>
