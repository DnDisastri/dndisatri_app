@props(['title' => null, 'icon' => null])

{{-- Bordo sottile e neutro per staccare la card dal fondo (in tema scuro le sta
     a un passo). `icon` opzionale, accanto al titolo (lo usa il Turno). --}}
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
