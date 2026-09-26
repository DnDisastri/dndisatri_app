{{--
    L'intenzione, riga per riga: cosa esce, cosa entra, e da quale personaggio.

    Le righe le costruisce `SupervisedAction::details()`, che è dove sta la
    lettura del `payload`. Qui si stampano e basta: se un giorno la forma dei
    dati cambia, cambia là e questa vista non se ne accorge.
--}}
@php $righe = $getRecord()->details(); @endphp

@if ($righe === [])
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Di questa richiesta non è rimasto scritto il dettaglio.
    </p>
@else
    <dl class="divide-y divide-gray-200 dark:divide-white/10">
        @foreach ($righe as $riga)
            <div class="flex flex-wrap gap-x-4 gap-y-1 py-2">
                <dt class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">{{ $riga['voce'] }}</dt>
                <dd class="text-sm text-gray-950 dark:text-white">{{ $riga['valore'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
