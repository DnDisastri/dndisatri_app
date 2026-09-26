@props(['character'])

{{--
    Le classi di un personaggio per esteso: un multiclasse è due cose insieme, e
    `$character->class` (la principale) lo appiattirebbe a una.

    Con una classe sola il livello non si ripete (lo scrive già chi chiama); con
    più di una conta quello di ciascuna. Senza righe caricate si ricade sulle
    colonne della scheda.
--}}
@php
    $righe = $character->relationLoaded('classes') ? $character->classes : $character->classes()->get();
    $multi = $righe->count() > 1;
@endphp

@if ($righe->isEmpty())
    {{ $character->class }}@if ($character->subclass) ({{ $character->subclass }})@endif
@else
    @foreach ($righe as $classe)
        {{ $classe->class }}@if ($multi) {{ $classe->level }}@endif @if ($classe->subclass)({{ $classe->subclass }})@endif @if (! $loop->last)/ @endif
    @endforeach
@endif
