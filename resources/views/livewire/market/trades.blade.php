<div>
    <p class="mb-4 text-sm text-muted">
        Gestisci le tue richieste di scambio, controlla le offerte ricevute e segui gli scambi in corso.
    </p>
    <x-market-nav :character="$character" :characters="$this->myCharacters()" :esito="$esito" />

    <div class="xl:grid xl:grid-cols-2 xl:items-start xl:gap-8">
    <div>
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

                {{-- Avvisa prima del clic con la stessa verifica dell'accettazione (`Trade::deliveryProblems`):
                     «Accetto» spento, «Rifiuta» resta acceso. --}}
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

    {{-- Separate dalle proposte: «Accetto» farebbe due cose diverse. --}}
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
                                $richiesta->offered_cp > 0 ? \App\Domain\Dnd\Coins::formatValue($richiesta->offered_cp) : null,
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

    </div>

    @if ($character)
        <x-card class="xl:sticky xl:top-8">
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

                    <x-campo-monete model="giveMonete" label="Monete" />
                </x-inset>

                <x-inset>
                    <p class="mb-2 text-sm font-semibold text-fg">Chiedo</p>

                    {{-- La sua vetrina, non lo zaino: lo zaino altrui non è pubblico. --}}
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

                    <x-campo-monete model="wantMonete" label="Monete" class="mb-3" />

                    <label for="chiedo" class="mb-1 block border-t border-line pt-3 text-xs text-muted">
                        Oppure chiedigli qualcosa che non vedi
                    </label>
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

            {{-- Il pulsante dice se parte una proposta o una richiesta. --}}
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
    </div>

    @if ($richiesta)
        <x-modal title="Ce l'ho" close="chiudiRichiesta">
            <div class="space-y-3 text-sm">
                <p class="text-muted">
                    <strong class="text-fg">{{ $richiesta->from?->name }}</strong> vorrebbe
                    «{{ $richiesta->wanted }}» e offre
                    <span class="text-fg">
                        {{ collect([
                            $richiesta->offeredNames()->implode(', ') ?: null,
                            $richiesta->offered_cp > 0 ? \App\Domain\Dnd\Coins::formatValue($richiesta->offered_cp) : null,
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

                    <x-campo-monete model="offroMonete" label="Monete" />
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
