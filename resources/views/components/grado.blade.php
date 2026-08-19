@props([
    'level',
    'conNome' => true,
    'size' => 'h-5 w-5',
])

{{--
    Il medaglione del grado d'avventuriero (richiesta 8).

    Cinque fasce dedotte dal livello, un metallo per fascia — legno, bronzo,
    argento, oro, platino. Si passa il **livello** e non il grado: chi scrive la
    vista ha in mano un personaggio col suo livello, e il grado è roba di
    dominio che si calcola qui, in un posto solo.

    Il colore lo mette il metallo, non il tema: sono cinque tinte che il tema
    non ha, e il medaglione dev'essere d'oro anche di notte. Per questo il colore
    è un `style` inline e non una classe — l'icona Phosphor prende `currentColor`
    dal contenitore, e il contenitore glielo dà.
--}}
@php $grado = \App\Domain\Dnd\AdventurerRank::fromLevel((int) $level); @endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}
      title="{{ $grado->label() }} · livelli {{ $grado->range() }} · {{ $grado->metal() }}">
    <span class="inline-flex" style="color: {{ $grado->color() }}">
        <x-icona :is="\App\Enums\Icon::Rank" :class="$size" />
    </span>

    @if ($conNome)
        <span class="text-sm font-medium text-fg">{{ $grado->label() }}</span>
    @endif
</span>
