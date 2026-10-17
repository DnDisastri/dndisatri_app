{{-- Solo dal catalogo: i talenti nuovi li aggiungono DM e admin dal pannello. --}}
<select name="feat_id" aria-label="Talento" class="{{ $campo }} mt-1">
    <option value="">Scegli il talento</option>
    @foreach ($feats as $talento)
        <option value="{{ $talento->id }}" @selected((string) old('feat_id') === (string) $talento->id)>
            {{ $talento->name }}{{ $talento->prerequisite ? ' ('.$talento->prerequisite.')' : '' }}
        </option>
    @endforeach
</select>
<p class="mt-1 text-xs text-muted">Manca quello che cerchi? Chiedi a un dungeon master di aggiungerlo.</p>
