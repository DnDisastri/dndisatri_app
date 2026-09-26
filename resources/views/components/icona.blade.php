@props(['is', 'class' => 'h-6 w-6'])

{{--
    L'unico modo di disegnare un'icona nelle pagine.

    `$is` è un caso di enum, non una stringa: si sceglie la cosa, non il disegno,
    e un nome sbagliato non esiste come caso. Va bene ogni enum che implementa
    `App\Contracts\Icona` (`Icon`, `Reaction`).

    Si chiama `icona` e non `icon` per non collidere con l'`<x-icon>` di
    blade-icons. La misura è un `@prop` e non una classe unita: `merge()` somma
    (`h-6 w-6 h-8 w-8`) e a parità di specificità vince l'ultima regola del
    foglio, non del tag — un'icona più piccola verrebbe ignorata in silenzio.
    Il colore non si passa: le Phosphor usano `currentColor`.
--}}
<x-dynamic-component :component="$is->blade()" :class="$class" {{ $attributes }} />
