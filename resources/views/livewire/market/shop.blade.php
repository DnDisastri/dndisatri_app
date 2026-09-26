<div class="mx-auto max-w-4xl px-4 py-6">
    <h2 class="mb-4 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Shop" class="h-7 w-7" /> Negozio della gilda
    </h2>

    
    <p class="mb-4 text-sm text-muted">
        Benvenuto nel negozio della gilda! Trova equipaggiamento, strumenti e oggetti utili per affrontare al meglio le tue prossime quest.
    </p>
    <x-market-nav attiva="market.shop" :character="$character" :characters="$this->myCharacters()" />

    <x-market-search placeholder="Cerca nel negozio" />

    {{-- Due blocchi e non due griglie diverse: i preferiti stanno in cima
         perché è la roba che si ricompra, e sotto c'è tutto il resto. Un
         articolo sta di qua o di là, mai in tutti e due i posti — vedersi la
         stessa card due volte in mezzo schermo fa dubitare di aver capito. --}}
    @php
        $sezioni = $preferiti->isEmpty()
            ? [null => $items]
            : ['I tuoi preferiti' => $preferiti, 'Tutto il negozio' => $items];
    @endphp

    @foreach ($sezioni as $titolo => $elenco)
        @if ($titolo)
            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">{{ $titolo }}</h3>
        @endif

        <div class="mb-6 grid grid-cols-2 gap-3">
            @forelse ($elenco as $item)
                <x-market-card :nome="$item->name" :prezzo="$item->price"
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
                        Il negozio è vuoto.
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

                @if ($oggetto->details)
                    <p class="text-fg">{{ $oggetto->details }}</p>
                @endif

                {{-- Prezzo e quantità sono due righe della stessa tabella:
                     etichetta a sinistra, valore a destra. La quantità sotto il
                     prezzo perché è quella che lo moltiplica. --}}
                <div class="space-y-2 border-t border-line pt-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-muted">Prezzo</span>
                        <strong class="text-lg text-on-accent-soft">
                            {{ number_format($oggetto->price, 0, ',', '.') }} mo
                        </strong>
                    </div>

                    @if ($character && $oggetto->isAvailable())
                        <div class="flex items-center justify-between gap-3">
                            <label for="quanti" class="text-muted">Quantità</label>
                            {{-- `.live` perché il totale qui sotto deve cambiare
                                 mentre si scrive: un totale che si aggiorna solo
                                 dopo aver premuto Compra arriverebbe tardi. --}}
                            <input id="quanti" type="number" min="1" wire:model.live="quanti"
                                   class="w-20 rounded-md border border-line bg-page px-2 py-1 text-right text-fg">
                        </div>
                    @endif
                </div>

                @if ($character)
                    @if (! $oggetto->isAvailable())
                        <x-note>Esaurito. Tornerà quando il capogilda rifornisce il negozio.</x-note>
                    @else
                        {{-- Le due righe che riguardano il premere stanno in
                             mezzo, sopra il pulsante: il totale solo se se ne
                             prende più d'uno, quanto manca solo se manca
                             davvero. Al centro perché parlano del pulsante che
                             hanno sotto, non della riga che hanno sopra.

                             Il pulsante resta premibile anche senza soldi: una
                             riga che spiega vale più di un pulsante spento. --}}
                        @if ($quantita > 1)
                            <p class="text-center text-muted">
                                in tutto <strong class="text-on-accent-soft">{{ number_format($totale, 0, ',', '.') }} mo</strong>
                            </p>
                        @endif

                        @if ($character->gp < $totale)
                            <p class="text-center text-xs text-muted">
                                ti mancano {{ number_format($totale - $character->gp, 0, ',', '.') }} mo
                            </p>
                        @endif

                        <x-button full type="button" wire:click="buy({{ $oggetto->id }})">Compra</x-button>

                        {{-- L'errore si ripete qui dentro. In cima alla pagina
                             c'è già, ma sta **sotto** il fondo scuro: un
                             acquisto che non riesce direbbe di no in un posto
                             che in quel momento non si vede. --}}
                        @error('mercato')
                            <x-note tone="danger">{{ $message }}</x-note>
                        @enderror
                    @endif

                    {{-- La riga sta sul contenitore e non sul pulsante: un
                         bordo dentro un `inline-flex` è lungo quanto le parole,
                         e sopra un pulsante a tutta larghezza si vedeva che
                         finiva a metà. --}}
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
