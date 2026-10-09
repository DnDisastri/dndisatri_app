@extends('layouts.app')
@section('title', $encounter->title)

@section('content')
@php $campo = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg'; @endphp

<x-pagina class="space-y-6">
    <x-back dove="sopra" :href="route('encounters.index', ['campagna' => $encounter->campaign->slug])">
        Tutti i combattimenti
    </x-back>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide text-muted">{{ $encounter->campaign->title }}</p>
            <h1 class="text-2xl leading-tight text-fg">{{ $encounter->title }}</h1>
            @if ($encounter->session)
                <a href="{{ route('sessions.show', ['session' => $encounter->session, 'da' => 'regia']) }}"
                   class="mt-1 inline-block text-sm text-muted hover:underline">{{ $encounter->session->displayTitle() }}</a>
            @endif
        </div>
    </div>

    <div class="space-y-6 xl:grid xl:grid-cols-[minmax(0,3fr)_minmax(0,1fr)] xl:items-start xl:gap-6 xl:space-y-0">
        <x-panel>
            <livewire:combat-tracker :encounter="$encounter" />
        </x-panel>

        <details class="rounded-card border border-line bg-surface px-4 py-3" @if (session('status') || $errors->any()) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-fg">Titolo, sessione, elimina</summary>

            <form method="POST" action="{{ route('encounters.update', $encounter) }}" class="mt-3 space-y-3">
                @csrf
                @method('PATCH')

                <label class="block text-sm">
                    <span class="mb-1 block text-muted">Titolo</span>
                    <input type="text" name="title" maxlength="120" required value="{{ old('title', $encounter->title) }}" class="{{ $campo }}">
                </label>
                @error('title') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror

                <label class="block text-sm">
                    <span class="mb-1 block text-muted">Sessione</span>
                    <select name="game_session_id" class="{{ $campo }}">
                        <option value="">Nessuna</option>
                        @foreach ($sessioni as $sessione)
                            <option value="{{ $sessione->id }}" @selected($encounter->game_session_id === $sessione->id)>
                                {{ $sessione->displayTitle() }} · {{ $sessione->played_at->translatedFormat('j M') }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <x-button variant="secondary" full>Salva</x-button>
            </form>

            <form method="POST" action="{{ route('encounters.destroy', $encounter) }}" class="mt-3 border-t border-line pt-3"
                  data-conferma="Eliminare «{{ $encounter->title }}»? I PF degli eroi restano come sono.">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-on-danger-soft hover:underline">Elimina il combattimento</button>
            </form>
        </details>
    </div>
</x-pagina>

@include('partials.conferma')
@endsection
