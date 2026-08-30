@props(['is', 'class' => 'h-6 w-6'])

{{--
    L'unico modo di disegnare un'icona nelle pagine.

    `$is` è un caso di enum e non una stringa: chi scrive la vista sceglie la
    **cosa** e non il disegno, e un nome sbagliato non arriva a schermo perché
    non esiste come caso. Vanno bene tutti gli enum che dichiarano
    `App\Contracts\Icona` — `Icon` per l'applicazione, `Reaction` per le
    faccine — perché qui serve soltanto che sappiano rispondere a `blade()`.

    Si chiama `icona` e non `icon` perché blade-icons ha già un suo `<x-icon>`,
    che vuole il nome del disegno: due componenti con lo stesso nome e vince il
    suo, con un errore poco chiaro.

    La misura è un `@prop` e non un attributo unito: `$attributes->merge()`
    **somma** invece di sostituire, e usciva `class="h-6 w-6 h-8 w-8"`. Non è
    un dettaglio estetico — a parità di specificità vince l'ultima regola del
    foglio, non l'ultima scritta nel tag, e Tailwind ordina le utility per
    valore crescente: chiedere un'icona più piccola di quella predefinita
    significava vedersela ignorare in silenzio.

    Il colore invece non si passa: le Phosphor usano `currentColor` e prendono
    quello del contenitore.
--}}
<x-dynamic-component :component="$is->blade()" :class="$class" {{ $attributes }} />
