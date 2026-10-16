<div>
    {{-- Navy e non rosso: l'azione principale della scheda resta del giocatore. --}}
    <p class="mb-2 text-xs uppercase tracking-wide text-muted">Strumenti da DM</p>

    <div class="flex flex-wrap gap-2">
        <x-button variant="secondary" size="sm" type="button" wire:click="apriOro">
            <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" /> Monete
        </x-button>

        <x-button variant="quiet" size="sm" type="button" wire:click="apriMorte">
            <x-icona :is="\App\Enums\Icon::Fallen" class="h-4 w-4" /> Dichiara caduto
        </x-button>

        <x-button variant="quiet" size="sm" type="button" wire:click="apriRitiro">
            <x-icona :is="\App\Enums\Icon::Archive" class="h-4 w-4" /> Ritira
        </x-button>

        @if ($ritirati->isNotEmpty())
            <x-button variant="quiet" size="sm" type="button" wire:click="apriPassaggio">
                <x-icona :is="\App\Enums\Icon::Sessions" class="h-4 w-4" /> Sessioni da un ritirato
            </x-button>
        @endif
    </div>

    @if ($esitoOro)
        <p class="mt-2 text-sm text-primary">{{ $esitoOro }}</p>
    @endif

    @if ($modaleOro)
        <x-modal title="Monete" close="annullaOro">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">
                    <span class="font-semibold text-fg">{{ $character->name }}</span>
                    ha adesso <x-monete :borsa="$character->coins()" class="font-semibold text-fg" />.
                </p>

                <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Dare o togliere">
                    <label @class([
                        'flex cursor-pointer items-center justify-center rounded-full border px-3 py-2 font-semibold',
                        'border-active bg-active text-on-active' => ! $oroTogli,
                        'border-line text-fg' => $oroTogli,
                    ])>
                        <input type="radio" wire:model.live="oroTogli" value="0" class="sr-only"> Dai
                    </label>
                    <label @class([
                        'flex cursor-pointer items-center justify-center rounded-full border px-3 py-2 font-semibold',
                        'border-active bg-active text-on-active' => $oroTogli,
                        'border-line text-fg' => ! $oroTogli,
                    ])>
                        <input type="radio" wire:model.live="oroTogli" value="1" class="sr-only"> Togli
                    </label>
                </div>

                <div>
                    <x-campo-monete model="oroMonete" label="Quante monete" />
                    <p class="mt-1 text-xs text-muted">
                        Togliendo si usano le monete esatte se ci sono, altrimenti il loro valore col resto. Mai sotto zero.
                    </p>
                </div>

                <div>
                    <label for="oro-motivo" class="block text-muted">Perché</label>
                    <input id="oro-motivo" type="text" wire:model="oroMotivo" maxlength="200"
                           placeholder="Premio di fine quest, ricompensa, correzione…"
                           class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2 text-fg placeholder:text-muted">
                    <p class="mt-1 text-xs text-muted">Lo legge il giocatore nel Registro. Senza, non si assegna.</p>
                    @error('oroMotivo') <p class="mt-1 text-on-danger-soft">{{ $message }}</p> @enderror
                </div>

                <x-button full type="button" wire:click="assegnaOro">{{ $oroTogli ? 'Togli' : 'Dai' }}</x-button>
            </div>
        </x-modal>
    @endif

    {{-- Irreversibile: serve la spunta. Racconto e sessione sono facoltativi. --}}
    @if ($modaleMorte)
        <x-modal title="Dichiara caduto" close="annullaMorte">
            <div class="space-y-3 text-left text-sm">
                <x-note tone="danger">
                    Stai per dichiarare caduto <span class="font-semibold">{{ $character->name }}</span>.
                    <span class="font-semibold">Non si torna indietro.</span>
                    Il giocatore potrà crearne uno nuovo.
                </x-note>

                <div>
                    <label for="morte-racconto" class="block text-muted">Com'è andata <span class="text-muted">(facoltativo)</span></label>
                    <textarea id="morte-racconto" wire:model="morteRacconto" rows="3" maxlength="2000"
                              placeholder="Il racconto che resterà nel memoriale."
                              class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2 text-fg placeholder:text-muted"></textarea>
                    @error('morteRacconto') <p class="mt-1 text-on-danger-soft">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="morte-sessione" class="block text-muted">In quale sessione <span class="text-muted">(facoltativo)</span></label>
                    <select id="morte-sessione" wire:model="morteSessione"
                            class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                        <option value="">Fra una sessione e l'altra</option>
                        @foreach ($sessioni as $sessione)
                            <option value="{{ $sessione->id }}">
                                {{ $sessione->displayTitle() }}@if ($sessione->campaign) · {{ $sessione->campaign->title }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-start gap-2">
                    <input type="checkbox" wire:model="morteCapito" class="mt-1 accent-[var(--ui-active)]">
                    <span class="text-fg">Capisco che è irreversibile.</span>
                </label>
                @error('morteCapito') <p class="text-on-danger-soft">{{ $message }}</p> @enderror

                <x-button variant="primary" full type="button" wire:click="dichiaraCaduto">
                    Dichiara caduto
                </x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="annullaMorte"
                            class="text-sm text-muted transition hover:text-fg">Non adesso</button>
                </div>
            </div>
        </x-modal>
    @endif
    @if ($modaleRitiro)
        <x-modal title="Ritira il personaggio" close="annullaRitiro">
            <div class="space-y-3 text-left text-sm">
                <x-note>
                    <span class="font-semibold">{{ $character->name }}</span> sparisce dall'app ma resta nel database,
                    con Registro e sessioni. Le sue richieste in attesa vengono rifiutate.
                    Il giocatore potrà crearne uno nuovo, e dalla sua scheda gli passerai le sessioni giocate.
                </x-note>

                <label class="flex items-start gap-2">
                    <input type="checkbox" wire:model="ritiroCapito" class="mt-1 accent-[var(--ui-active)]">
                    <span class="text-fg">Ne avete parlato col giocatore.</span>
                </label>
                @error('ritiroCapito') <p class="text-on-danger-soft">{{ $message }}</p> @enderror

                <x-button variant="primary" full type="button" wire:click="ritira">Ritira</x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="annullaRitiro"
                            class="text-sm text-muted transition hover:text-fg">Non adesso</button>
                </div>
            </div>
        </x-modal>
    @endif

    @if ($modalePassaggio)
        <x-modal title="Sessioni da un personaggio ritirato" close="annullaPassaggio">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">
                    Le sessioni giocate, con il diario, passano a <span class="font-semibold text-fg">{{ $character->name }}</span>.
                    Monete e oggetti restano dove sono.
                </p>

                <select wire:model="passaggioDa" aria-label="Da quale personaggio"
                        class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                    <option value="">Scegli il personaggio ritirato</option>
                    @foreach ($ritirati as $ritirato)
                        <option value="{{ $ritirato->id }}">
                            {{ $ritirato->name }} · {{ $ritirato->sessions_count === 1 ? '1 sessione' : $ritirato->sessions_count.' sessioni' }}
                        </option>
                    @endforeach
                </select>
                @error('passaggioDa') <p class="text-on-danger-soft">{{ $message }}</p> @enderror

                <x-button full type="button" wire:click="passaSessioni">Passa le sessioni</x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="annullaPassaggio"
                            class="text-sm text-muted transition hover:text-fg">Non adesso</button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
