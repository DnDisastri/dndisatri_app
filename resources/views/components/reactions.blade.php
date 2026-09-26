@props(['for'])

{{--
    La fila delle reaction: un modulo solo con dieci pulsanti (ognuno manda il
    suo valore, un solo token CSRF, niente JS; dieci moduli sarebbero HTML non
    valido).

    Toccare l'accesa la toglie, un'altra la sostituisce. Il numero compare solo
    da uno in su.
--}}
@php
    use App\Enums\Reactable;
    use App\Enums\Reaction;

    $conteggi = $for->reactionCounts();
    $mia = $for->reactionOf(auth()->user());
@endphp

<form method="POST" action="{{ route('reactions.store', [Reactable::of($for)->value, $for->getKey()]) }}"
      {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    @csrf

    @foreach (Reaction::cases() as $reazione)
        @php $quante = (int) ($conteggi[$reazione->value] ?? 0); @endphp

        {{-- `aria-pressed`: per chi ascolta è l'unica cosa che distingue «l'ho messa io» da «c'è». --}}
        <button type="submit" name="reazione" value="{{ $reazione->value }}"
                title="{{ $reazione->label() }}" aria-label="{{ $reazione->label() }}"
                aria-pressed="{{ $mia === $reazione ? 'true' : 'false' }}"
                @class([
                    'flex items-center gap-1 rounded-full border px-2.5 py-1.5 text-xs font-semibold transition',
                    // Navy pieno, niente rosso: qui il rosso vuol dire solo «sei qui».
                    'border-primary bg-primary text-on-primary' => $mia === $reazione,
                    'border-line bg-surface text-muted hover:border-active' => $mia !== $reazione,
                ])>
            <x-icona :is="$reazione" class="h-5 w-5" />

            @if ($quante > 0)
                <span>{{ $quante }}</span>
            @endif
        </button>
    @endforeach
</form>
