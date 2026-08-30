<div class="mx-auto max-w-4xl px-4 py-6">
    <h2 class="mb-4 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Listings" class="h-7 w-7" /> Annunci
    </h2>
    <p class="mb-4 text-sm text-muted">
        Fai affari con i tuoi compagni di gilda: esplora gli oggetti offerti in scambio e cogli le migliori occasioni per ottenere ciò che ti serve.
    </p>

    <x-market-nav attiva="market.listings" :character="$character" :characters="$this->myCharacters()" />

    @if ($character)
        {{-- Mettere in vendita. Sta in cima perché è la cosa che si viene a
             fare qui: comprare capita, vendere si decide. --}}
        <x-card class="mb-6">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Metti in vendita</h3>

            <div class="space-y-3">
                <div>
                    <label for="itemName" class="mb-1 block text-sm text-fg">Dallo zaino</label>
                    <select id="itemName" wire:model="itemName"
                            class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
                        <option value="">Scegli un oggetto…</option>
                        @foreach ($mine as $item)
                            <option value="{{ $item->name }}">
                                {{ $item->name }} @if ($item->qty > 1) (ne hai {{ $item->qty }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3">
                    <div class="w-24">
                        <label for="sellQty" class="mb-1 block text-sm text-fg">Quantità</label>
                        <input id="sellQty" type="number" min="1" wire:model="sellQty"
                               class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
                    </div>

                    <div class="flex-1">
                        <label for="price" class="mb-1 block text-sm text-fg">Prezzo in monete d'oro</label>
                        <input id="price" type="number" min="0" wire:model="price"
                               class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
                    </div>
                </div>

                <x-button size="lg" full type="button" wire:click="sell">Pubblica l'annuncio</x-button>

                <p class="text-xs text-muted">
                    L'oggetto esce subito dallo zaino e resta in deposito finché qualcuno compra
                    o finché ritiri l'annuncio.
                </p>
            </div>
        </x-card>
    @endif

    <x-market-search placeholder="Cerca fra gli annunci" />

    {{-- Due blocchi: la propria roba, che si ritira, e quella degli altri, che
         si compra. Sono due gesti diversi, e in un elenco solo bisognava
         leggere ogni card per sapere quale dei due si stava guardando.

         La stessa griglia dell'emporio, e la stessa card: comprare da un
         giocatore o dal negozio è lo stesso gesto, e due bacheche vestite
         diverse lo farebbero sembrare un gesto diverso. --}}
    @php
        $sezioni = $miei->isEmpty()
            ? ['In vendita' => $listings]
            : ['I miei oggetti' => $miei, 'In vendita dagli altri' => $listings];
    @endphp

    @foreach ($sezioni as $titolo => $elenco)
        <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">{{ $titolo }}</h3>

        <div class="mb-6 grid grid-cols-2 gap-3">
            @forelse ($elenco as $listing)
                {{-- Nel blocco dei propri il venditore non si scrive: lo dice
                     già il titolo, e ripeterlo su ogni card sarebbe la stessa
                     riga dodici volte. --}}
                <x-market-card :prezzo="$listing->price" :apri="'apri('.$listing->id.')'"
                               :nome="$listing->name.($listing->qty > 1 ? ' ×'.$listing->qty : '')"
                               :meta="collect([
                                   $character && $listing->seller_character_id === $character->getKey()
                                       ? null
                                       : 'di '.$listing->seller?->name,
                                   $listing->category,
                               ])->filter()->implode(' · ')" />
            @empty
                <x-empty class="col-span-full">
                    @if ($cerca !== '')
                        Nessun annuncio per «{{ $cerca }}».
                    @elseif ($miei->isEmpty())
                        Non c'è niente in vendita. Il primo annuncio potrebbe essere il tuo.
                    @else
                        Nessun altro sta vendendo niente.
                    @endif
                </x-empty>
            @endforelse
        </div>
    @endforeach

    @if ($annuncio)
        @php $mio = $character && $annuncio->seller_character_id === $character->getKey(); @endphp

        <x-modal :title="$annuncio->name">
            <div class="space-y-3 text-sm">
                <p class="text-muted">
                    {{ $mio ? 'Il tuo annuncio' : 'di '.$annuncio->seller?->name }}
                    @if ($annuncio->category) · {{ $annuncio->category }} @endif
                    @if ($annuncio->qty > 1) · ne vende {{ $annuncio->qty }} @endif
                </p>

                @if ($annuncio->details)
                    <p class="text-fg">{{ $annuncio->details }}</p>
                @endif

                <div class="flex items-baseline justify-between border-t border-line pt-3">
                    <span class="text-muted">Prezzo</span>
                    <strong class="text-lg text-on-accent-soft">
                        {{ number_format($annuncio->price, 0, ',', '.') }} mo
                    </strong>
                </div>

                @if ($character)
                    @if ($mio)
                        <p class="text-center text-xs text-muted">ritirandolo, l'oggetto torna nel tuo zaino</p>

                        <x-button full variant="quiet" type="button" wire:click="withdraw({{ $annuncio->id }})">
                            Ritira
                        </x-button>
                    @else
                        {{-- Quanto manca sta sopra il pulsante, al centro, e
                             c'è solo se manca davvero: si legge prima di
                             premere. Il pulsante resta premibile anche senza
                             soldi — una riga che spiega vale più di un pulsante
                             spento. --}}
                        @if ($character->gp < $annuncio->price)
                            <p class="text-center text-xs text-muted">
                                ti mancano {{ number_format($annuncio->price - $character->gp, 0, ',', '.') }} mo
                            </p>
                        @endif

                        <x-button full variant="secondary" type="button" wire:click="buy({{ $annuncio->id }})">
                            Compra
                        </x-button>
                    @endif

                    @error('mercato')
                        <x-note tone="danger">{{ $message }}</x-note>
                    @enderror
                @endif
            </div>
        </x-modal>
    @endif
</div>
