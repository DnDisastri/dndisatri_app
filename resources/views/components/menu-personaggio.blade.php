@props(['character'])

@php
    /*
     * Il menù dei tre pallini di un personaggio, uno solo per i due posti che lo
     * mostrano: la card de «I miei eroi» e l'intestazione della scheda. Erano
     * due copie identiche da tenere allineate a mano — stesse voci, stesse
     * icone — e una restava sempre indietro rispetto all'altra.
     *
     * `serve` è il permesso che ogni voce richiede: le quattro proposte le fa
     * solo chi può proporre (il proprietario di un personaggio **vivo**), il
     * registro chi può leggerlo (il proprietario e chi conduce). Le voci che non
     * passano il permesso non si disegnano; se non ne resta nessuna, i tre
     * pallini spariscono — meglio che aprire il vuoto.
     *
     * Quelle senza pagina restano **spente**, come le voci della barra in basso:
     * si vedono, si capisce che esistono, e non portano da nessuna parte.
     */
    $azioni = [
        ['nome' => 'Proponi modifiche', 'rotta' => 'proposals.edit', 'serve' => 'propose', 'icona' => \App\Enums\Icon::Edit],
        ['nome' => 'Sali di livello', 'rotta' => 'proposals.level-up', 'serve' => 'propose', 'icona' => \App\Enums\Icon::LevelUp],
        ['nome' => 'Registra un bottino', 'rotta' => 'proposals.loot', 'serve' => 'propose', 'icona' => \App\Enums\Icon::Loot],
        ['nome' => 'Oggetto magico', 'rotta' => 'proposals.item-effect', 'serve' => 'propose', 'icona' => \App\Enums\Icon::MagicItem],
        // Il registro sta staccato dalle proposte: non è una richiesta al DM, è
        // l'estratto conto che si legge, e il permesso è un altro.
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
                    {{-- Spenta è un `button disabled` e non uno `span` travestito:
                         così la tastiera la salta da sé. --}}
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
