{{-- I dati dell'ospite, uguali nel modulo di una sessione e in quello del calendario. --}}

{{-- Trappola per i bot: nascosta a chi usa il modulo davvero. --}}
<div class="hidden" aria-hidden="true">
    <label for="sito_web">Lascia vuoto</label>
    <input type="text" name="sito_web" id="sito_web" tabindex="-1" autocomplete="off">
</div>

<x-field name="name" label="Come ti chiami" autocomplete="name" required />
<x-field name="email" label="Email" type="email" autocomplete="email" required />

<div>
    <p class="mb-2 text-sm font-medium text-fg">Come ti contattiamo, oltre all'email <span class="font-normal text-muted">(facoltativo)</span></p>
    <div class="flex flex-col gap-4">
        <x-field name="phone" label="Telefono" type="tel" autocomplete="tel" />
        <x-field name="social" label="Instagram o Telegram" />
    </div>
</div>

<div>
    <label for="note" class="mb-1 block text-sm font-medium text-fg">
        Sai già che tipo di personaggio vuoi giocare? <span class="font-normal text-muted">(facoltativo)</span>
    </label>
    <textarea name="note" id="note" rows="3" maxlength="255"
              placeholder="Scrivicelo qui: un mago, qualcosa di cattivo, quello che ti incuriosisce…"
              class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-fg transition placeholder:text-muted focus:border-active focus:outline-none">{{ old('note') }}</textarea>
    @error('note') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
</div>

<label class="flex items-start gap-2 text-sm text-muted">
    <input type="checkbox" name="privacy" value="1" required class="mt-1 accent-active" @checked(old('privacy'))>
    <span>Va bene che il dungeon master usi questi dati per organizzare la sessione e contattarmi.</span>
</label>
@error('privacy') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror
