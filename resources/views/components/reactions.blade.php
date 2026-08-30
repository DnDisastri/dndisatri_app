@props(['for'])

{{--
    La fila delle reaction.

    **Un modulo solo con dieci pulsanti**, non dieci moduli: ogni pulsante
    manda il proprio valore con `name`/`value`, quindi c'è un `@csrf` solo e
    niente javascript. Dieci moduli annidati sarebbero anche HTML non valido.

    Toccare la faccina già accesa la toglie, toccarne un'altra sostituisce
    quella di prima. Non c'è un pulsante «togli»: sarebbe un secondo comando
    per la stessa cosa, e un secondo bersaglio da centrare col pollice.

    Il numero compare **solo da uno in su**. Dieci zeri in fila sarebbero dieci
    volte la stessa non-informazione, e farebbero sembrare vuoto quello che è
    solo nuovo.
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

        {{-- `aria-pressed` e non una classe soltanto: per chi ascolta la
             pagina è l'unica cosa che distingue «l'ho messa io» da «c'è». --}}
        <button type="submit" name="reazione" value="{{ $reazione->value }}"
                title="{{ $reazione->label() }}" aria-label="{{ $reazione->label() }}"
                aria-pressed="{{ $mia === $reazione ? 'true' : 'false' }}"
                @class([
                    'flex items-center gap-1 rounded-full border px-2.5 py-1.5 text-xs font-semibold transition',
                    // Navy pieno, e **niente rosso**: il rosso in questa
                    // applicazione vuol dire una cosa sola, «sei qui». Il
                    // primo tentativo metteva un bordo rosso attorno a un
                    // fondo crema con l'icona marrone — tre tinte di tre
                    // famiglie diverse — e per giunta dava al rosso un secondo
                    // significato. Una tinta sola, quella dell'applicazione.
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
