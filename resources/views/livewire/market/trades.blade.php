<div class="mx-auto max-w-2xl px-4 py-6">
    <h2 class="mb-4 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Trades" class="h-7 w-7" /> Scambi
    </h2>
    <p class="mb-4 text-sm text-muted">
        Gestisci le tue richieste di scambio, controlla le offerte ricevute e segui gli scambi in corso.
    </p>
    <x-market-nav attiva="market.trades" :character="$character" :characters="$this->myCharacters()" />

    {{-- Quelle arrivate stanno in cima: sono le uniche che aspettano qualcosa
         da te, e sono il motivo per cui uno apre questa pagina. --}}
    <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">
        Arrivate @if ($received->isNotEmpty()) <span class="text-fg">({{ $received->count() }})</span> @endif
    </h3>

    <div class="mb-6 space-y-2">
        @forelse ($received as $trade)
            @php $problemi = $trade->deliveryProblems(); @endphp

            <x-card>
                <p class="mb-2 text-sm text-muted">
                    <strong class="text-fg">{{ $trade->from?->name }}</strong> ti propone
                </p>

                <x-trade-offer :trade="$trade" />

                @if ($trade->message)
                    <x-inset padding="sm" class="mt-3 text-sm text-muted">«{{ $trade->message }}»</x-inset>
                @endif

                {{-- Se nel frattempo qualcosa non torna più — l'oggetto venduto,
                     l'oro speso — lo si dice **prima** del clic. La verifica è la
                     stessa che farebbe l'accettazione (`Trade::deliveryProblems`),
                     e «Accetto» è spento: premere per sentirsi dire di no è il
                     modo peggiore di scoprirlo. Rifiutare invece resta acceso —
                     una proposta che non si può più fare è proprio quella che
                     conviene chiudere. --}}
                @if ($problemi !== [])
                    <x-note tone="danger" class="mt-3">
                        <span class="font-semibold">Non si può più fare:</span>
                        {{ implode('; ', $problemi) }}.
                    </x-note>
                @endif

                <div class="mt-3 flex gap-2">
                    <x-button class="flex-1" type="button" wire:click="accept({{ $trade->id }})"
                              :disabled="$problemi !== []">Accetto</x-button>
                    <x-button variant="quiet" type="button" wire:click="reject({{ $trade->id }})">Rifiuto</x-button>
                </div>
            </x-card>
        @empty
            <x-empty>Nessuno ti ha proposto niente.</x-empty>
        @endforelse
    </div>

    {{-- Le richieste stanno sotto le proposte e non insieme: una proposta si
         accetta e la roba passa, una richiesta è una domanda a cui bisogna
         rispondere scegliendo cosa dare. Mescolarle vorrebbe dire due pulsanti
         «Accetto» che fanno due cose diverse. --}}
    @if ($richiesteArrivate->isNotEmpty())
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">
            Ti hanno chiesto <span class="text-fg">({{ $richiesteArrivate->count() }})</span>
        </h3>

        <div class="mb-6 space-y-2">
            @foreach ($richiesteArrivate as $richiesta)
                <x-card>
                    <p class="text-sm text-muted">
                        <strong class="text-fg">{{ $richiesta->from?->name }}</strong> vorrebbe
                    </p>

                    <p class="my-1 text-lg text-fg">«{{ $richiesta->wanted }}»</p>

                    <p class="text-sm text-muted">
                        e in cambio offre
                        <span class="text-fg">
                            {{ collect([
                                $richiesta->offeredNames()->implode(', ') ?: null,
                                $richiesta->offered_gp > 0 ? $richiesta->offered_gp.' mo' : null,
                            ])->filter()->implode(' e ') ?: 'niente' }}
                        </span>
                    </p>

                    @if ($richiesta->message)
                        <x-inset padding="sm" class="mt-3 text-sm text-muted">«{{ $richiesta->message }}»</x-inset>
                    @endif

                    {{-- «Ce l'ho» e non «Accetto»: accettando non si conclude
                         niente, si sceglie cosa dare e parte una proposta. --}}
                    <div class="mt-3 flex gap-2">
                        <x-button class="flex-1" type="button" wire:click="apriRichiesta({{ $richiesta->id }})">
                            Ce l'ho
                        </x-button>
                        <x-button variant="quiet" type="button" wire:click="rifiutaRichiesta({{ $richiesta->id }})">
                            Non ce l'ho
                        </x-button>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    @if ($sent->isNotEmpty())
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Mandate</h3>

        <div class="mb-6 space-y-2">
            @foreach ($sent as $trade)
                <x-card>
                    <p class="mb-2 text-sm text-muted">
                        A <strong class="text-fg">{{ $trade->to?->name }}</strong>, in attesa di risposta
                    </p>

                    <x-trade-offer :trade="$trade" />

                    <x-button variant="quiet" class="mt-3" type="button" wire:click="withdraw({{ $trade->id }})">
                        Ritira
                    </x-button>
                </x-card>
            @endforeach
        </div>
    @endif

    @if ($richiesteMandate->isNotEmpty())
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Chieste</h3>

        <div class="mb-6 space-y-2">
            @foreach ($richiesteMandate as $richiesta)
                <x-card>
                    <p class="text-sm text-muted">
                        A <strong class="text-fg">{{ $richiesta->to?->name }}</strong>, hai chiesto
                    </p>

                    <p class="my-1 text-fg">«{{ $richiesta->wanted }}»</p>

                    <x-button variant="quiet" class="mt-2" type="button"
                              wire:click="ritiraRichiesta({{ $richiesta->id }})">
                        Ritira
                    </x-button>
                </x-card>
            @endforeach
        </div>
    @endif

    @if ($character)
        <x-card>
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Proponi uno scambio</h3>

            <div class="mb-4">
                <label for="toCharacterId" class="mb-1 block text-sm text-fg">A chi</label>
                <select id="toCharacterId" wire:model.live="toCharacterId"
                        class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
                    <option value="">Scegli un personaggio…</option>
                    @foreach ($others as $other)
                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Le due offerte affiancate: è l'unico modo di vedere a colpo
                 d'occhio se lo scambio è equo. Su telefono vanno in colonna,
                 ma restano nell'ordine «do» → «chiedo». --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <x-inset>
                    <p class="mb-2 text-sm font-semibold text-fg">Do</p>

                    <div class="mb-2 space-y-1">
                        @forelse ($mine as $item)
                            <label class="flex items-center gap-2 text-sm text-fg">
                                <input type="checkbox" value="{{ $item->name }}" wire:model="give"
                                       class="rounded border-line accent-[var(--ui-active)]">
                                {{ $item->name }}
                            </label>
                        @empty
                            <p class="text-xs text-muted">Lo zaino è vuoto.</p>
                        @endforelse
                    </div>

                    <label class="block text-xs text-muted">Monete d'oro</label>
                    <input type="number" min="0" wire:model="giveGp"
                           class="w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">
                </x-inset>

                <x-inset>
                    <p class="mb-2 text-sm font-semibold text-fg">Chiedo</p>

                    {{-- Quello che si vede è la **sua vetrina**, non il suo
                         zaino: lo zaino di un altro non è pubblico, e quello
                         che c'è qui ce l'ha messo lui. --}}
                    <div class="mb-2 space-y-1">
                        @forelse ($theirs as $item)
                            <label class="flex items-center gap-2 text-sm text-fg">
                                <input type="checkbox" value="{{ $item->name }}" wire:model="want"
                                       class="rounded border-line accent-[var(--ui-active)]">
                                {{ $item->name }}
                            </label>
                        @empty
                            <p class="text-xs text-muted">
                                {{ $toCharacterId
                                    ? 'Non ha messo niente in vetrina.'
                                    : 'Scegli prima a chi proporre.' }}
                            </p>
                        @endforelse
                    </div>

                    <label class="mb-1 block text-xs text-muted">Monete d'oro</label>
                    <input type="number" min="0" wire:model="wantGp"
                           class="mb-3 w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">

                    {{-- La via per quello che in vetrina non c'è. Il nome si
                         scrive a mano perché è una diceria: «mi han detto che
                         hai un amuleto». Può essere sbagliato, e va bene — a
                         dire se ce l'ha è lui. --}}
                    <label for="chiedo" class="mb-1 block border-t border-line pt-3 text-xs text-muted">
                        Oppure chiedigli qualcosa che non vedi
                    </label>
                    {{-- Il segnaposto non è il nome di un oggetto vero: qui
                         dentro finisce quello che si è sentito dire, e un
                         esempio troppo concreto lo si copia invece di
                         scriverci il proprio. --}}
                    <input id="chiedo" type="text" maxlength="120" wire:model.live="chiedo"
                           placeholder="Che cosa hai sentito dire?"
                           class="w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg placeholder:text-muted">
                </x-inset>
            </div>

            <div class="mt-4">
                <label for="message" class="mb-1 block text-sm text-fg">Due parole (facoltative)</label>
                <input id="message" type="text" wire:model="message" maxlength="255"
                       class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg">
            </div>

            {{-- Il pulsante dice quale delle due cose sta per partire: sono due
                 strade diverse — una si accetta e basta, l'altra torna
                 indietro come proposta — e chi preme deve saperlo prima. --}}
            @if ($chiedo !== '')
                <p class="mt-4 text-center text-xs text-muted">
                    Se ce l'ha, ti manderà lui la proposta da confermare.
                </p>
            @endif

            <x-button size="lg" full class="mt-2" type="button" wire:click="propose">
                {{ $chiedo !== '' ? 'Manda la richiesta' : 'Manda la proposta' }}
            </x-button>
        </x-card>
    @endif

    {{-- Rispondere a una richiesta: si sceglie dal proprio zaino cosa dare,
         perché quello che è arrivato erano solo parole. --}}
    @if ($richiesta)
        <x-modal title="Ce l'ho" close="chiudiRichiesta">
            <div class="space-y-3 text-sm">
                <p class="text-muted">
                    <strong class="text-fg">{{ $richiesta->from?->name }}</strong> vorrebbe
                    «{{ $richiesta->wanted }}» e offre
                    <span class="text-fg">
                        {{ collect([
                            $richiesta->offeredNames()->implode(', ') ?: null,
                            $richiesta->offered_gp > 0 ? $richiesta->offered_gp.' mo' : null,
                        ])->filter()->implode(' e ') ?: 'niente' }}.
                    </span>
                </p>

                <div class="border-t border-line pt-3">
                    <p class="mb-2 font-semibold text-fg">Cosa gli dai</p>

                    <div class="mb-3 space-y-1">
                        @forelse ($mine as $item)
                            <label class="flex items-center gap-2 text-fg">
                                <input type="checkbox" value="{{ $item->name }}" wire:model="offro"
                                       class="rounded border-line accent-[var(--ui-active)]">
                                {{ $item->name }}
                            </label>
                        @empty
                            <p class="text-xs text-muted">Lo zaino è vuoto.</p>
                        @endforelse
                    </div>

                    <label for="offroGp" class="mb-1 block text-xs text-muted">Monete d'oro</label>
                    <input id="offroGp" type="number" min="0" wire:model="offroGp"
                           class="w-full rounded-md border border-line bg-page px-2 py-1 text-fg">
                </div>

                @error('scambio')
                    <x-note tone="danger">{{ $message }}</x-note>
                @enderror

                <p class="text-center text-xs text-muted">
                    Parte una proposta: la roba si muove quando lui conferma.
                </p>

                <x-button full type="button" wire:click="accettaRichiesta">Manda la proposta</x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="rifiutaRichiesta({{ $richiesta->id }})"
                            class="text-sm text-muted transition hover:text-fg">
                        Non ce l'ho, rifiuta
                    </button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
