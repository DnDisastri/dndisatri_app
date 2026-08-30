@props(['character'])

{{--
    Le classi di un personaggio, scritte per esteso.

    Serve perché un multiclasse è **due cose insieme** e finora si leggeva come
    una sola: le pagine stampavano `$character->class`, che è la copia della
    classe principale tenuta sulla scheda, e un Guerriero 3 / Mago 2 compariva
    come «Guerriero».

    Con una classe sola il livello non si ripete qui: chi chiama lo scrive già
    accanto («liv. 5»). Con più di una invece il livello **di ciascuna** è
    l'informazione che conta, e la somma resta quella scritta fuori.

    Le righe vere possono non esserci — un personaggio costruito al volo in un
    test, o uno vecchio non ancora convertito — e allora si ricade sulle colonne
    della scheda, che per un monoclasse dicono la stessa cosa.
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
