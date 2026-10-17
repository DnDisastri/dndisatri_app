{{-- Le righe degli oggetti con la ricerca fra catalogo, Emporio e bottini già approvati.
     Una sola volta per pagina: il JavaScript cerca questi `data-` nel documento. --}}
<script type="application/json" data-suggerimenti-bottino>@json($suggerimenti)</script>

@error('items')
    <p class="mb-3 text-sm text-on-danger-soft">{{ $message }}</p>
@enderror

<div data-righe-bottino>
    @for ($i = 0; $i < $massimo; $i++)
        <div data-riga-bottino @if ($i >= $aperte) hidden @endif
             class="border-t border-line py-3 first:border-0 first:pt-0">
            <div class="grid grid-cols-6 gap-2">
                <input type="hidden" name="items[{{ $i }}][magic_item_id]" value="{{ old("items.$i.magic_item_id") }}" data-campo="magic_item_id">
                <div class="relative col-span-6 sm:col-span-4" data-cerca-oggetto>
                    <input type="text" name="items[{{ $i }}][name]" placeholder="Nome (cerca)" maxlength="100"
                           value="{{ old("items.$i.name") }}" aria-label="Nome dell'oggetto {{ $i + 1 }}"
                           autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list"
                           data-campo="name" class="{{ $campo }}">
                    <ul data-elenco hidden role="listbox"
                        class="absolute inset-x-0 top-full z-20 mt-1 max-h-64 overflow-y-auto rounded-md border-2 border-line bg-surface py-1 shadow-lg"></ul>
                </div>
                <input type="number" name="items[{{ $i }}][qty]" placeholder="Q.tà" min="1" max="999"
                       value="{{ old("items.$i.qty") }}" aria-label="Quantità"
                       class="{{ $campo }} col-span-2 sm:col-span-1">
                <input type="number" name="items[{{ $i }}][value]" placeholder="Valore" min="0" step="0.01"
                       value="{{ old("items.$i.value") }}" aria-label="Valore in mo" data-campo="value"
                       class="{{ $campo }} col-span-4 sm:col-span-1">
                <select name="items[{{ $i }}][category]" aria-label="Categoria" data-campo="category"
                        class="{{ $campo }} col-span-6 sm:col-span-2">
                    <option value="">Categoria</option>
                    @foreach (\App\Models\CharacterItem::CATEGORIES as $categoria)
                        <option value="{{ $categoria }}" @selected(old("items.$i.category") === $categoria)>{{ $categoria }}</option>
                    @endforeach
                </select>
                <select name="items[{{ $i }}][base]" aria-label="Che tipo di arma, armatura o scudo è" data-campo="base"
                        class="{{ $campo }} col-span-6 sm:col-span-4">
                    <option value="">Tipo (armi, armature, scudi)</option>
                    @foreach (\App\Enums\EquipmentSlot::bases() as $gruppo => $basi)
                        <optgroup label="{{ $gruppo }}">
                            @foreach ($basi as $base)
                                <option value="{{ $base }}" @selected(old("items.$i.base") === $base)>{{ $base }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <textarea name="items[{{ $i }}][details]" rows="2" placeholder="Dettagli (facoltativo)" maxlength="1000"
                          aria-label="Dettagli" data-campo="details" class="{{ $campo }} col-span-6">{{ old("items.$i.details") }}</textarea>
            </div>

            @foreach (['name', 'qty', 'category', 'value', 'base', 'details'] as $chiave)
                @error("items.$i.$chiave")
                    <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                @enderror
            @endforeach
        </div>
    @endfor
</div>

<div class="mt-3">
    <div data-aggiungi-oggetto @if ($aperte >= $massimo) hidden @endif>
        <x-button type="button" variant="quiet" size="sm">Aggiungi un oggetto</x-button>
    </div>
    <p data-limite-oggetti @if ($aperte < $massimo) hidden @endif class="text-sm text-muted">
        {{ $limite }}
    </p>
</div>
