{{-- La sessione da cui arriva la richiesta: la notifica va ai suoi DM, oltre agli admin. --}}
@php $giocate = $character->playedSessions(); @endphp

@if ($giocate->isNotEmpty())
    <x-panel title="Da quale sessione">
        <label class="block text-sm">
            <span class="mb-1 block text-muted">Così la richiesta arriva ai dungeon master che l'hanno condotta.</span>
            <select name="game_session_id" class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg">
                @foreach ($giocate as $sessione)
                    <option value="{{ $sessione->id }}" @selected((int) old('game_session_id', $giocate->first()->id) === $sessione->id)>
                        {{ $sessione->played_at->translatedFormat('j M') }} · {{ $sessione->campaign?->title }} · {{ $sessione->displayTitle() }}
                    </option>
                @endforeach
                <option value="" @selected(old('game_session_id') === '')>Nessuna in particolare</option>
            </select>
        </label>
        @error('game_session_id') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
    </x-panel>
@endif
