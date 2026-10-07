<div>
    <x-intro :page="\App\Enums\IntroPage::Shop" class="mb-4" />
    <x-market-nav :character="$character" :characters="$this->myCharacters()" :esito="$esito" />

    <x-market-search placeholder="Cerca nell'Emporio">
        <select wire:model.live="ordine" aria-label="Ordina" title="Ordina"
                class="shrink-0 rounded-xl border border-line bg-surface px-2 py-2 text-sm text-fg focus:border-active focus:outline-none">
            @foreach (\App\Livewire\Market\Shop::ORDINI as $valore => $etichetta)
                <option value="{{ $valore }}">{{ $etichetta }}</option>
            @endforeach
        </select>
    </x-market-search>

    {{-- Un articolo sta nei preferiti o nel resto, mai in entrambi. --}}
    @php
        $sezioni = $preferiti->isEmpty()
            ? [null => $items]
            : ['I tuoi preferiti' => $preferiti, 'Tutto l\'Emporio' => $items];
    @endphp

    @foreach ($sezioni as $titolo => $elenco)
        @if ($titolo)
            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">{{ $titolo }}</h3>
        @endif

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            @forelse ($elenco as $item)
                <x-market-card :nome="$item->name" :prezzo="$item->price_cp"
                               :apri="'apri('.$item->id.')'"
                               :meta="collect([
                                   $item->category,
                                   $item->is_unlimited ? null : 'ne restano '.$item->stock,
                               ])->filter()->implode(' · ')">
                    @if ($character)
                        <x-slot:angolo>
                            @php $stellato = $stelle->contains($item->getKey()); @endphp

                            <button type="button" wire:click="preferisci({{ $item->id }})"
                                    aria-pressed="{{ $stellato ? 'true' : 'false' }}"
                                    aria-label="{{ $stellato ? 'Togli dai preferiti' : 'Metti fra i preferiti' }}"
                                    title="{{ $stellato ? 'Togli dai preferiti' : 'Metti fra i preferiti' }}"
                                    @class([
                                        'rounded-full p-1 transition',
                                        'text-on-accent-soft' => $stellato,
                                        'text-muted hover:text-fg' => ! $stellato,
                                    ])>
                                <x-icona :is="$stellato ? \App\Enums\Icon::Favorite : \App\Enums\Icon::NotFavorite"
                                         class="h-5 w-5" />
                            </button>
                        </x-slot:angolo>
                    @endif
                </x-market-card>
            @empty
                <x-empty class="col-span-full">
                    @if ($cerca !== '')
                        Niente che somigli a «{{ $cerca }}».
                    @elseif ($preferiti->isEmpty())
                        L'Emporio è vuoto.
                    @else
                        Non c'è altro oltre ai tuoi preferiti.
                    @endif
                </x-empty>
            @endforelse
        </div>
    @endforeach

    @if ($oggetto)
        @php
            $quantita = max(1, $quanti);
            $totale = $oggetto->totalPrice($quantita);
        @endphp

        <x-modal :title="$oggetto->name">
            <div class="space-y-3 text-sm">
                <p class="text-muted">
                    {{ $oggetto->category ?: 'Senza categoria' }}
                    · {{ $oggetto->is_unlimited ? 'sempre disponibile' : 'ne restano '.$oggetto->stock }}
                </p>

                @if (($oggetto->base && $oggetto->base !== $oggetto->name) || $oggetto->magic_bonus)
                    <p class="text-muted">
                        Tipo: {{ trim(($oggetto->base ?? $oggetto->name).($oggetto->magic_bonus ? ' +'.$oggetto->magic_bonus : '')) }}
                    </p>
                @endif

                @if ($oggetto->details)
                    <p class="text-fg">{{ $oggetto->details }}</p>
                @endif

                <x-effetti-oggetto :effetti="$oggetto->effects" />

                <div class="space-y-2 border-t border-line pt-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-muted">Prezzo</span>
                        <strong class="text-lg text-on-accent-soft"><x-monete :valore="$oggetto->price_cp" /></strong>
                    </div>

                    @if ($character && $oggetto->isAvailable())
                        <div class="flex items-center justify-between gap-3">
                            <label for="quanti" class="text-muted">Quantità</label>
                            {{-- `.live`: il totale deve cambiare mentre si scrive. --}}
                            <input id="quanti" type="number" min="1" wire:model.live="quanti"
                                   class="w-20 rounded-md border border-line bg-page px-2 py-1 text-right text-fg">
                        </div>
                    @endif
                </div>

                @if ($character)
                    @if (! $oggetto->isAvailable())
                        <x-note>Esaurito. Tornerà quando il capogilda rifornisce l'Emporio.</x-note>
                    @else
                        {{-- Totale solo se più d'uno, quanto manca solo se manca.
                             Il pulsante resta premibile anche senza soldi: la riga spiega perché. --}}
                        @if ($quantita > 1)
                            <p class="text-center text-muted">
                                in tutto <strong class="text-on-accent-soft"><x-monete :valore="$totale" /></strong>
                            </p>
                        @endif

                        @if ($character->purseValue() < $totale)
                            <p class="text-center text-xs text-muted">
                                ti mancano <x-monete :valore="$totale - $character->purseValue()" />
                            </p>
                        @endif

                        <x-button full type="button" wire:click="buy({{ $oggetto->id }})">Compra</x-button>

                        {{-- Ripetuto qui: l'errore in cima alla pagina sta sotto il fondo scuro. --}}
                        @error('mercato')
                            <x-note tone="danger">{{ $message }}</x-note>
                        @enderror

                        <div class="space-y-2 border-t border-line pt-3">
                            <p class="font-semibold text-fg">Oppure barattalo</p>

                            @if ($offribili->isEmpty())
                                <p class="text-xs text-muted">
                                    Nel tuo zaino non c'è niente che valga almeno <x-monete :valore="$oggetto->price_cp" />
                                </p>
                            @else
                                <p class="text-xs text-muted">
                                    Dai un tuo oggetto che vale almeno il prezzo; non c'è resto. Lo approva un dungeon master.
                                </p>
                                <select wire:model="offerta" aria-label="Oggetto da offrire"
                                        class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                                    <option value="">Scegli cosa offri</option>
                                    @foreach ($offribili as $offribile)
                                        <option value="{{ $offribile->id }}">
                                            {{ $offribile->name }} ({{ \App\Domain\Dnd\Coins::formatValue($offribile->value_cp) }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-button full variant="quiet" type="button" wire:click="barter({{ $oggetto->id }})">Proponi il baratto</x-button>
                            @endif

                            @error('baratto')
                                <x-note tone="danger">{{ $message }}</x-note>
                            @enderror
                        </div>
                    @endif

                    {{-- Bordo sul contenitore: su un `inline-flex` sarebbe lungo quanto il testo. --}}
                    <div class="border-t border-line pt-3">
                        <button type="button" wire:click="preferisci({{ $oggetto->id }})"
                                class="flex items-center gap-1.5 text-sm text-muted transition hover:text-fg">
                            @if ($stelle->contains($oggetto->getKey()))
                                <x-icona :is="\App\Enums\Icon::Favorite" class="h-5 w-5 text-on-accent-soft" />
                                Togli dai preferiti
                            @else
                                <x-icona :is="\App\Enums\Icon::NotFavorite" class="h-5 w-5" />
                                Metti fra i preferiti
                            @endif
                        </button>
                    </div>
                @endif
            </div>
        </x-modal>
    @endif
</div>
