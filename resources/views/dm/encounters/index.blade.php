@extends('layouts.app')
@section('title', 'Combattimenti')

@section('content')
@php
    use App\Enums\Icon;

    $campo = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg';
@endphp

<x-pagina class="space-y-6">
    <x-back dove="sopra" :href="route('dm.home', array_filter(['campagna' => $campagna?->slug]))">
        Torna all'Area Master
    </x-back>

    <div>
        <p class="text-xs uppercase tracking-wide text-muted">{{ $campagna?->title ?? 'Nessuna campagna' }}</p>
        <h1 class="text-2xl text-fg">Combattimenti</h1>
        <x-intro :page="\App\Enums\IntroPage::Encounters" class="mt-1" />
    </div>

    @if ($campagna === null)
        <x-empty>Non ci sono campagne attive.</x-empty>
    @else
        <div class="space-y-6 lg:grid lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start lg:gap-6 lg:space-y-0">
            <section class="space-y-3">
                <h2 class="text-xs uppercase tracking-wide text-muted">Da condurre</h2>

                @forelse ($aperti as $scontro)
                    @include('dm.encounters.partials.riga', ['scontro' => $scontro])
                @empty
                    <x-empty>Nessun combattimento preparato.</x-empty>
                @endforelse

                @if ($conclusi->isNotEmpty())
                    <details class="group">
                        <summary class="cursor-pointer list-none text-xs uppercase tracking-wide text-muted [&::-webkit-details-marker]:hidden">
                            Conclusi ({{ $conclusi->count() }}) <span class="group-open:hidden">›</span>
                        </summary>
                        <div class="mt-3 space-y-2">
                            @foreach ($conclusi as $scontro)
                                @include('dm.encounters.partials.riga', ['scontro' => $scontro])
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>

            <x-panel title="Nuovo combattimento">
                <form method="POST" action="{{ route('encounters.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="campaign_id" value="{{ $campagna->id }}">

                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Titolo</span>
                        <input type="text" name="title" maxlength="120" required value="{{ old('title') }}"
                               placeholder="Imboscata sul ponte" class="{{ $campo }}">
                    </label>
                    @error('title') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror

                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Sessione (facoltativa, anche dopo)</span>
                        <select name="game_session_id" class="{{ $campo }}">
                            <option value="">Nessuna</option>
                            @foreach ($sessioni as $sessione)
                                <option value="{{ $sessione->id }}" @selected((int) old('game_session_id', $sessioneScelta) === $sessione->id)>
                                    {{ $sessione->displayTitle() }} · {{ $sessione->played_at->translatedFormat('j M') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <x-button full>
                        <x-icona :is="Icon::Sessions" class="mr-1.5 h-4 w-4" /> Crea e prepara
                    </x-button>
                </form>
            </x-panel>
        </div>
    @endif
</x-pagina>
@endsection
