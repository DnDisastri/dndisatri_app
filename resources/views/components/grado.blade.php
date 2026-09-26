@props([
    'level',
    'conNome' => true,
    'size' => 'h-5 w-5',
])

{{--
    Il medaglione del grado d'avventuriero: cinque fasce dal livello, un metallo
    per fascia. Si passa il livello, il grado si calcola qui.

    Il colore è un `style` inline e non una classe: sono tinte che il tema non ha
    (il medaglione è d'oro anche di notte), e l'icona Phosphor prende `currentColor`.
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
