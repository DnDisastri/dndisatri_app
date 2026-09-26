<div>
    {{-- I due comandi da tavolo. Navy e non rosso: sono azioni pari, e nessuna
         è «l'azione» della schermata — quella resta del giocatore. Stanno su
         una riga sola, e su schermo stretto vanno in colonna. --}}
    <p class="mb-2 text-xs uppercase tracking-wide text-muted">Strumenti da DM</p>

    <div class="flex flex-wrap gap-2">
        <x-button variant="secondary" size="sm" type="button" wire:click="apriOro">
            <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" /> Assegna oro
        </x-button>

        <x-button variant="quiet" size="sm" type="button" wire:click="apriMorte">
            <x-icona :is="\App\Enums\Icon::Fallen" class="h-4 w-4" /> Dichiara caduto
        </x-button>
    </div>

    @if ($esitoOro)
        <p class="mt-2 text-sm text-primary">{{ $esitoOro }}</p>
    @endif

    {{-- ASSEGNA ORO (M18).

         Quanto e perché. Il motivo è obbligatorio perché finisce nel Registro,
         ed è lì che mesi dopo si capisce da dove è arrivato l'oro. Anche in
         negativo — una multa, un furto raccontato — ma mai sotto zero: a quello
         pensa `GrantGold`, che non fa scendere il saldo sotto lo zero. --}}
    @if ($modaleOro)
        <x-modal title="Assegna oro" close="annullaOro">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">
                    A <span class="font-semibold text-fg">{{ $character->name }}</span>,
                    che adesso ha <span class="font-semibold text-fg">{{ $character->gp }} mo</span>.
                </p>

                <div>
                    <label for="oro-importo" class="block text-muted">Quanto oro</label>
                    <input id="oro-importo" type="number" wire:model="oroImporto" autofocus
                           class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2 text-lg text-fg">
                    <p class="mt-1 text-xs text-muted">Negativo per toglierne: una multa, un furto. Mai sotto zero.</p>
                    @error('oroImporto') <p class="mt-1 text-on-danger-soft">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="oro-motivo" class="block text-muted">Perché</label>
                    <input id="oro-motivo" type="text" wire:model="oroMotivo" maxlength="200"
                           placeholder="Premio di fine quest, ricompensa, correzione…"
                           class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2 text-fg placeholder:text-muted">
                    <p class="mt-1 text-xs text-muted">Lo legge il giocatore nel Registro. Senza, non si assegna.</p>
                    @error('oroMotivo') <p class="mt-1 text-on-danger-soft">{{ $message }}</p> @enderror
                </div>

                <x-button full type="button" wire:click="assegnaOro">Assegna</x-button>
            </div>
        </x-modal>
    @endif

    {{-- DICHIARA CADUTO (M19).

         Irreversibile, e trattato come tale: la spunta di conferma non è una
         formalità, è il gesto che dice «so cosa sto facendo». Il racconto e la
         serata sono facoltativi — qualcuno muore fra una sessione e l'altra, e
         il racconto si può scrivere dopo. La serata, quando c'è, diventa il
         link al resoconto nella Hall of Fallen Heroes (P15b). --}}
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
                    <label for="morte-sessione" class="block text-muted">In quale serata <span class="text-muted">(facoltativo)</span></label>
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
</div>
