@props(['effetti' => null])

@if (! empty($effetti))
    @php $descritti = collect($effetti)->map(fn ($e) => \App\Models\CharacterItemEffect::describeCopy($e))->join(', '); @endphp
    <p class="text-on-accent-soft">Effetto magico, con la sintonia: {{ $descritti }}</p>
@endif
