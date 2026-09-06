@props(['character'])

@php
    /*
     * Il menù dei tre pallini di un personaggio, uno solo per i due posti che
     * lo mostrano (card «I miei eroi» e intestazione scheda).
     *
     * `serve` è il permesso di ogni voce: le proposte solo chi può proporre
     * (proprietario di un personaggio vivo), il registro chi può leggerlo. Le
     * voci senza permesso non si disegnano; se non ne resta nessuna, i pallini
     * spariscono. Quelle senza pagina restano spente.
     */
    $azioni = [
        ['nome' => 'Proponi modifiche', 'rotta' => 'proposals.edit', 'serve' => 'propose', 'icona' => \App\Enums\Icon::Edit],
        ['nome' => 'Sali di livello', 'rotta' => 'proposals.level-up', 'serve' => 'propose', 'icona' => \App\Enums\Icon::LevelUp],
        ['nome' => 'Registra un bottino', 'rotta' => 'proposals.loot', 'serve' => 'propose', 'icona' => \App\Enums\Icon::Loot],
        ['nome' => 'Oggetto magico', 'rotta' => 'proposals.item-effect', 'serve' => 'propose', 'icona' => \App\Enums\Icon::MagicItem],
        // Il registro è staccato dalle proposte: è l'estratto conto, altro permesso.
        ['nome' => 'Registro del personaggio', 'rotta' => 'characters.ledger', 'serve' => 'viewLedger', 'separa' => true, 'icona' => \App\Enums\Icon::CharacterLedger],
    ];

    $voci = collect($azioni)->filter(
        fn (array $azione) => auth()->user()?->can($azione['serve'], $character),
    );
@endphp

@if ($voci->isNotEmpty())
    <details class="relative shrink-0">
        <summary title="Altro" aria-label="Altre azioni per {{ $character->name }}"
            class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full
                   text-muted transition hover:bg-page hover:text-fg [&::-webkit-details-marker]:hidden">
            <x-icona :is="\App\Enums\Icon::Menu" class="h-6 w-6" />
        </summary>

        <nav class="absolute right-0 z-40 mt-2 w-60 overflow-hidden rounded-xl border border-line
                    bg-surface shadow-lg shadow-black/10">
            @foreach ($voci as $voce)
                @if (\Illuminate\Support\Facades\Route::has($voce['rotta']))
                    <a href="{{ route($voce['rotta'], $character) }}" @class([
                        'flex items-center gap-3 px-4 py-2.5 text-sm text-fg hover:bg-page',
                        'border-t border-line' => $voce['separa'] ?? false,
                    ])>
                        <x-icona :is="$voce['icona']" class="h-5 w-5 shrink-0 text-muted" />
                        {{ $voce['nome'] }}
                    </a>
                @else
                    {{-- Spenta è un `button disabled`, non uno `span`: la tastiera la salta. --}}
                    <button type="button" disabled title="Non c'è ancora" @class([
                        'flex w-full cursor-not-allowed items-center gap-3 px-4 py-2.5 text-left text-sm text-muted opacity-60',
                        'border-t border-line' => $voce['separa'] ?? false,
                    ])>
                        <x-icona :is="$voce['icona']" class="h-5 w-5 shrink-0" />
                        {{ $voce['nome'] }}
                    </button>
                @endif
            @endforeach
        </nav>
    </details>
@endif
