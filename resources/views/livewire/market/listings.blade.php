<div>
    <p class="mb-4 text-sm text-muted">
        Fai affari con i tuoi compagni di gilda: esplora gli oggetti offerti in scambio e cogli le migliori occasioni per ottenere ciò che ti serve.
    </p>

    <x-market-nav :character="$character" :characters="$this->myCharacters()" :esito="$esito" />

    @if ($character)
        <x-card class="mb-6">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Metti in vendita</h3>

            <div class="space-y-3">
                <div class="space-y-3 md:flex md:items-end md:gap-3 md:space-y-0">
                <div class="md:flex-1">
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

                <div class="w-24">
                    <label for="sellQty" class="mb-1 block text-sm text-fg">Quantità</label>
                    <input id="sellQty" type="number" min="1" wire:model="sellQty"
                           class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
                </div>
                </div>

                <x-campo-monete model="price" label="Prezzo" class="md:max-w-sm" />

                <x-button size="lg" full type="button" wire:click="sell" class="md:ml-auto md:flex md:w-fit">Pubblica l'annuncio</x-button>

                <p class="text-xs text-muted">
                    L'oggetto esce subito dallo zaino e resta in deposito finché qualcuno compra
                    o finché ritiri l'annuncio.
                </p>
            </div>
        </x-card>
    @endif

    <x-market-search placeholder="Cerca fra gli annunci" />

    @php
        $sezioni = $miei->isEmpty()
            ? ['In vendita' => $listings]
            : ['I miei oggetti' => $miei, 'In vendita dagli altri' => $listings];
    @endphp

    @foreach ($sezioni as $titolo => $elenco)
        <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">{{ $titolo }}</h3>

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            @forelse ($elenco as $listing)
                {{-- Nei propri il venditore non si ripete. --}}
                <x-market-card :prezzo="$listing->price_cp" :apri="'apri('.$listing->id.')'"
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
                    <strong class="text-lg text-on-accent-soft"><x-monete :valore="$annuncio->price_cp" /></strong>
                </div>

                @if ($character)
                    @if ($mio)
                        <p class="text-center text-xs text-muted">ritirandolo, l'oggetto torna nel tuo zaino</p>

                        <x-button full variant="quiet" type="button" wire:click="withdraw({{ $annuncio->id }})">
                            Ritira
                        </x-button>
                    @else
                        {{-- Il pulsante resta premibile anche senza soldi: la riga sopra spiega perché. --}}
                        @if ($character->purseValue() < $annuncio->price_cp)
                            <p class="text-center text-xs text-muted">
                                ti mancano <x-monete :valore="$annuncio->price_cp - $character->purseValue()" />
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
