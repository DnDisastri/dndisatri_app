@props(['trade'])

@php
    // Sempre offre / in cambio, anche per chi riceve: la stessa proposta si legge in un modo solo.
    $lati = [
        ['titolo' => 'Offre', 'oggetti' => $trade->givenItems(), 'oro' => $trade->give_cp],
        ['titolo' => 'In cambio di', 'oggetti' => $trade->wantedItems(), 'oro' => $trade->want_cp],
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
                    <li class="font-semibold"><x-monete :valore="$lato['oro']" /></li>
                @endif

                @if ($lato['oggetti']->isEmpty() && ! $lato['oro'])
                    <li class="text-muted">niente</li>
                @endif
            </ul>
        </x-inset>
    @endforeach
</div>
