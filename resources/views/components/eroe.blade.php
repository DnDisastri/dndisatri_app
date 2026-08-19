@props(['character', 'warn' => false])

{{--
    La card di un personaggio in Gilda (P13).

    **Una sola card per i vivi e per i caduti.** I caduti stavano su una pagina
    a sé con una card scritta un'altra volta, e le due si erano già allontanate:
    una scriveva le classi per intero e l'altra la sola principale, una aveva la
    foto e l'altra no. Erano la stessa cosa scritta due volte, e due cose uguali
    scritte due volte diventano diverse da sole.

    Chi è vivo e chi non lo è lo sa già il personaggio, quindi non serve dirglielo
    da fuori: la card guarda `isAlive()` e cambia da sé le tre cose che cambiano
    — dove porta, il colore della foto, e la riga della morte.

    Chi è, e basta: foto, nome, di che pasta è fatto, e chi lo gioca. **Niente
    classe armatura e niente punti ferita** — sono numeri di una serata, cambiano
    di ora in ora, e su una bacheca dicono solo quanto era ferito qualcuno
    l'ultima volta che ha giocato. Chi ha bisogno di quei numeri apre la scheda.

    L'oro non si mostra per una ragione più forte: quanto ha in tasca il compagno
    cambia le trattative, e non in meglio.

    La forma è quella di tutte le card: `rounded-card` e bordo neutro. Prima era
    un `rounded-lg` (8px contro 18) con il bordo rosso fisso, e il rosso vuol dire
    una cosa sola — «sei qui» — quindi su venti card in fila non diceva più
    niente. Si accende al passaggio, come ogni riquadro cliccabile.
--}}
@php $vivo = $character->isAlive(); @endphp

{{-- Un vivo porta alla sua scheda, un caduto al suo memoriale (P15b): sulla
     scheda di un morto non c'è niente da fare, e la cosa che si va a cercare
     cliccandoci sopra è **com'è andata**. --}}
<a href="{{ $vivo ? route('characters.show', $character) : route('fallen.show', $character) }}"
   @class([
       'flex gap-4 rounded-card border border-line bg-surface p-4 transition items-center',
       'hover:border-active hover:-translate-y-1 hover:shadow-lg hover:shadow-black/10',
       // Il caduto è **spento**, non rotto: si legge tutto, ma si vede da
       // lontano che quella card non è come le altre.
       'opacity-75 hover:opacity-100' => ! $vivo,
   ])>
    @if ($character->photoUrl())
        {{-- In grigio, per i caduti. È il segno che si vede prima di aver
             letto qualunque parola. --}}
        <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
             @class(['h-20 w-20 shrink-0 rounded-lg object-cover', 'grayscale' => ! $vivo])>
    @else
        {{-- Il segnaposto tiene la misura della foto: senza, le card di chi ce
             l'ha e di chi no non starebbero in fila. --}}
        <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-page">
            <x-icona :is="$vivo ? \App\Enums\Icon::Characters : \App\Enums\Icon::Fallen" class="h-8 w-8 text-muted" />
        </span>
    @endif

    <div class="flex flex-row-reverse w-full h-full gap-1 justify-between items-center">

        {{-- Il livello nell'angolo, in una pillola: è il numero che si cerca
             quando si scorre la gilda — «con chi posso giocare stasera» — e in
             fondo a una riga di testo si trovava solo leggendola tutta. Sta in
             cima e non al centro perché il nome è la prima cosa, e le due si
             leggono su una riga.

             Resta accent anche sui caduti, e non diventa danger: dice a che
             livello è arrivato, che è un fatto e non una brutta notizia. Il
             segno della morte sta sotto, dove si legge per intero. --}}
        <x-badge tone="accent" size="sm" class="shrink-0 self-start">
            liv. {{ $character->level }}
        </x-badge>

        <div class="min-w-0 flex-1 space-y-2">
            <h3 class="flex items-center gap-1.5 text-lg font-normal font-display text-fg ">
                {{ $character->name }}
                @unless ($vivo)
                    <x-icona :is="\App\Enums\Icon::Fallen" class="h-4 w-4 shrink-0 text-on-danger-soft" />
                @endunless

                {{-- Il segno di chi è sotto richiamo: lo vede solo il DM (chi
                     passa `warn`), ed è il triangolo, non il divieto — un
                     avvertimento, non una condanna. --}}
                @if ($warn)
                    <span title="Il giocatore è sotto richiamo">
                        <x-icona :is="\App\Enums\Icon::Warnings" class="h-4 w-4 shrink-0 text-on-danger-soft" />
                    </span>
                @endif
            </h3>
            <x-grado :level="$character->level" />

            @unless ($vivo)
                <p class="mt-1 text-xs text-on-danger-soft">
                    Caduto il {{ $character->died_at->translatedFormat('j F Y') }}
                </p>
            @endunless
        </div>
    </div>
</a>
