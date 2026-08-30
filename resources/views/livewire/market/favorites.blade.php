{{-- La scheda la guardano anche gli altri (P14): la lista è privata come lo
     zaino, e a chi non è suo questo componente non dice niente. Il pannello lo
     nasconde già la vista della scheda — questo è il secondo giro di chiave. --}}
<div>
    @if ($mio)
    <x-panel title="Preferiti dell'emporio">
        @if ($items->isEmpty())
            <p class="text-sm text-muted">
                Ancora nessuno. All'<a href="{{ route('market.shop') }}" class="text-fg underline">emporio</a>
                la stella accanto a un articolo lo mette qui, per non doverlo cercare ogni volta.
            </p>
        @else
            <ul class="space-y-2">
                @foreach ($items as $item)
                    <li class="flex items-center gap-2">
                        {{-- Il collegamento porta all'emporio **su quell'articolo**:
                             il riquadro è già aperto e si compra da lì. Portare
                             all'emporio e basta vorrebbe dire ricominciare a
                             cercare, che è la cosa che i preferiti evitano. --}}
                        <a href="{{ route('market.shop', ['oggetto' => $item->id]) }}"
                           class="group min-w-0 flex-1">
                            <span class="block truncate font-semibold text-fg group-hover:underline">
                                {{ $item->name }}
                            </span>
                            <span class="block truncate text-xs text-muted">
                                {{ $item->category }}
                                @unless ($item->isAvailable()) · esaurito @endunless
                            </span>
                        </a>

                        <span class="shrink-0 text-sm font-bold text-on-accent-soft">
                            {{ number_format($item->price, 0, ',', '.') }} mo
                        </span>

                        {{-- Da qui si toglie soltanto: la stella è già piena, e
                             premerla la spegne. Metterla si fa dove l'articolo
                             si vede per intero. --}}
                        <button type="button" wire:click="togli({{ $item->id }})"
                                aria-label="Togli {{ $item->name }} dai preferiti"
                                title="Togli dai preferiti"
                                class="shrink-0 rounded-full p-1 text-on-accent-soft transition hover:text-fg">
                            <x-icona :is="\App\Enums\Icon::Favorite" class="h-5 w-5" />
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>
    @endif
</div>
