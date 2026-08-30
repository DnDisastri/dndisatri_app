@props(['character' => null, 'characters' => null, 'attiva' => null])

@php
    $porte = [
        'market.shop' => 'Emporio',
        'market.listings' => 'Annunci',
        'market.trades' => 'Scambi',
    ];

    /*
     * Quale porta è aperta.
     *
     * **Arriva da fuori e non da `request()->routeIs()`**, che qui dentro
     * mentirebbe: questo componente vive dentro una pagina Livewire, e ogni
     * volta che la pagina si ridisegna da sola — si apre un riquadro, si
     * scrive nella ricerca — la richiesta in corso è una POST a
     * `/livewire/update`, non l'indirizzo della pagina. La linguetta si
     * spegneva al primo clic, e restava spenta.
     *
     * Il `routeIs` resta come rete per chi usa il componente da una pagina
     * normale senza passare niente.
     */
    $qui = $attiva;
@endphp

<div class="mb-5">
    {{-- Le tre porte del mercato. Sono linguette e non pillole colorate: la
         barra in basso usa già primario e attivo, e ripeterli qui sopra
         farebbe due navigazioni che si contendono l'occhio. --}}
    <nav class="mb-4 flex rounded-xl border border-line bg-surface p-1 text-sm">
        @foreach ($porte as $rotta => $nome)
            @php $aperta = $qui ? $qui === $rotta : request()->routeIs($rotta); @endphp

            <a href="{{ route($rotta) }}" wire:navigate
               @if ($aperta) aria-current="page" @endif
               @class([
                   'flex-1 rounded-lg px-3 py-2 text-center font-medium transition',
                   'bg-primary text-on-primary' => $aperta,
                   'text-muted hover:text-fg' => ! $aperta,
               ])>
                {{ $nome }}
            </a>
        @endforeach
    </nav>

    @if ($character)
        <x-card padding="sm" class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm">
                {{-- Un DM gioca anche lui e può avere più personaggi: sceglie
                     con quale sta comprando. Con uno solo non c'è niente da
                     scegliere e la tendina non compare. --}}
                @if ($characters && $characters->count() > 1)
                    <select wire:model.live="characterId"
                            class="rounded-md border border-line bg-page px-2 py-1 text-sm font-semibold text-fg">
                        @foreach ($characters as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                @else
                    <strong class="text-fg">{{ $character->name }}</strong>
                @endif
                <span class="block text-xs text-muted">stai comprando come</span>
            </div>

            <x-badge tone="accent" size="md">{{ number_format($character->gp, 0, ',', '.') }} mo</x-badge>
        </x-card>
    @else
        <x-note>Serve un personaggio in salute per vendere o comprare.</x-note>
    @endif

    @if (session('mercato'))
        <x-note class="mt-3">{{ session('mercato') }}</x-note>
    @endif

    @error('mercato')
        <x-note tone="danger" class="mt-3">{{ $message }}</x-note>
    @enderror

    @error('scambio')
        <x-note tone="danger" class="mt-3">{{ $message }}</x-note>
    @enderror
</div>
