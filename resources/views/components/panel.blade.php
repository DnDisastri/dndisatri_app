@props(['title' => null, 'icon' => null])

{{-- Il bordo è sottile e neutro: serve a staccare la card dal fondo, che in
     tema scuro le sta a un passo di luminosità. Prima era rosso e spesso, e
     con venti pannelli in colonna gridava.

     `icon` è opzionale: quando c'è, sta accanto al titolo. La usa il Turno, dove
     un segno accanto ad «Azione», «Reazione» eccetera fa distinguere il costo a
     colpo d'occhio. --}}
<section {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-4']) }}>
    @if ($title)
        <h3 class="mb-3 flex items-center gap-2 border-b border-line pb-2 text-sm font-bold uppercase tracking-wide text-fg">
            @if ($icon)
                <x-icona :is="$icon" class="h-4 w-4 shrink-0 text-muted" />
            @endif
            {{ $title }}
        </h3>
    @endif

    {{ $slot }}
</section>
