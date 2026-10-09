@props(['character', 'warn' => false])

{{-- La card di un personaggio in Gilda, vivo o caduto. Niente CA, PF né monete:
     cambiano di continuo, e le monete altererebbero le trattative. --}}
@php $vivo = $character->isAlive(); @endphp

{{-- Un vivo alla scheda, un caduto al memoriale: lì la cosa che si cerca è com'è andata. --}}
<a href="{{ $vivo ? route('characters.show', $character) : route('fallen.show', $character) }}"
   @class([
       'flex gap-4 rounded-card border border-line bg-surface p-4 transition items-center',
       'hover:border-active hover:-translate-y-1 hover:shadow-lg hover:shadow-black/10',
       // Il caduto è spento, non rotto: si legge tutto ma si vede che è diverso.
       'opacity-75 hover:opacity-100' => ! $vivo,
   ])>
    @if ($character->photoUrl())
        {{-- In grigio per i caduti: il segno che si vede prima di leggere. --}}
        <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
             @class(['h-20 w-20 shrink-0 rounded-lg object-cover', 'grayscale' => ! $vivo])>
    @else
        {{-- Il segnaposto tiene la misura della foto, così le card restano in fila. --}}
        <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-page">
            <x-icona :is="$vivo ? \App\Enums\Icon::Characters : \App\Enums\Icon::Fallen" class="h-8 w-8 text-muted" />
        </span>
    @endif

    <div class="flex flex-row-reverse w-full h-full gap-1 justify-between items-center">

        {{-- Il livello in pillola: è il numero che si cerca scorrendo la gilda.
             Resta accent anche sui caduti (è un fatto); la morte si legge sotto. --}}
        <x-badge tone="accent" size="sm" class="shrink-0 self-start">
            liv. {{ $character->level }}
        </x-badge>

        <div class="min-w-0 flex-1 space-y-2">
            <h3 class="flex items-center gap-1.5 text-lg font-normal font-display text-fg ">
                {{ $character->name }}
                @unless ($vivo)
                    <x-icona :is="\App\Enums\Icon::Fallen" class="h-4 w-4 shrink-0 text-on-danger-soft" />
                @endunless

                {{-- Sotto richiamo: lo vede solo il DM (chi passa `warn`); triangolo, non divieto. --}}
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
