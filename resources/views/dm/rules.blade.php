@extends('layouts.app')
@section('title', 'Regole del DM')

@section('content')

@php use App\Enums\Icon; @endphp

<x-pagina larghezza="stretta">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="Icon::DmRules" class="h-7 w-7" /> Regole del DM
    </h2>
    <p class="mb-6 text-sm text-muted">Le regole della casa, uguali per tutti i tavoli. Le vedono solo DM e admin.</p>

    @forelse ($gruppi as $categoria => $regole)
        <section class="mt-8 first:mt-0">
            @if ($categoria !== '')
                <h3 class="mb-3 text-lg font-semibold text-fg">{{ $categoria }}</h3>
            @endif

            <div class="space-y-2">
                @foreach ($regole as $regola)
                    <x-panel>
                        <p class="font-semibold text-fg">{{ $regola->title }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $regola->body }}</p>
                    </x-panel>
                @endforeach
            </div>
        </section>
    @empty
        <x-empty size="lg">Ancora nessuna regola: le scrivono gli admin dal pannello.</x-empty>
    @endforelse

    @if (auth()->user()->isAdmin())
        <x-button variant="quiet" class="mt-6" :href="route('filament.admin.resources.dm-rules.index')">
            <x-icona :is="Icon::Edit" class="h-4 w-4" /> Modifica dal pannello
        </x-button>
    @endif

    <x-back :href="route('dm.home')">Torna all'Area Master</x-back>
</x-pagina>
@endsection
