@props(['trade'])

@php
    /*
     * Le due metà di uno scambio, sempre nello stesso ordine: quello che dà
     * chi ha proposto, e quello che chiede in cambio.
     *
     * L'ordine non si inverte per chi riceve. Sarebbe più "naturale" leggere
     * prima quello che si ottiene, ma allora la stessa proposta si
     * leggerebbe in due modi diversi a seconda di chi la guarda, e una
     * discussione al tavolo diventerebbe impossibile.
     */
    $lati = [
        ['titolo' => 'Offre', 'oggetti' => $trade->givenItems(), 'oro' => $trade->give_gp],
        ['titolo' => 'In cambio di', 'oggetti' => $trade->wantedItems(), 'oro' => $trade->want_gp],
    ];
@endphp

<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($lati as $lato)
        <x-inset padding="sm">
            <p class="mb-1 text-xs uppercase tracking-wide text-muted">{{ $lato['titolo'] }}</p>

            <ul class="text-sm text-fg">
                @foreach ($lato['oggetti'] as $item)
                    <li>
                        {{ $item->name }}
                        @if ($item->qty > 1)
                            <span class="text-muted">×{{ $item->qty }}</span>
                        @endif
                    </li>
                @endforeach

                @if ($lato['oro'] > 0)
                    <li class="font-semibold">{{ number_format($lato['oro'], 0, ',', '.') }} mo</li>
                @endif

                @if ($lato['oggetti']->isEmpty() && ! $lato['oro'])
                    <li class="text-muted">niente</li>
                @endif
            </ul>
        </x-inset>
    @endforeach
</div>
