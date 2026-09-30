@extends('layouts.app')
@section('title', 'Build consigliate')

@section('content')
<x-pagina>
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Builds" class="h-7 w-7" /> Build consigliate
    </h2>
    <p class="mb-6 text-sm text-muted">
        Personaggi di 1° già pensati, per partire senza studiarsi il manuale.
        Le sfogli sempre; per usarne una serve non avere già un personaggio.
    </p>

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
    @forelse ($builds as $build)
        <x-card :href="route('builds.show', $build)" flush padding="none">
            @if ($build->coverUrl())
                <div class="relative">
                    <img src="{{ $build->coverUrl() }}" alt="" class="h-24 w-full object-cover">
                    @if ($build->tag)
                        <span class="absolute left-2 top-2"><x-badge tone="accent">{{ $build->tag }}</x-badge></span>
                    @endif
                </div>
            @endif

            <div class="p-4">
                @if (! $build->coverUrl() && $build->tag)
                    <x-badge tone="accent" class="mb-1">{{ $build->tag }}</x-badge>
                @endif
                <p class="font-semibold text-fg">{{ $build->title }}</p>
                <p class="text-xs text-muted">
                    {{ $build->class }}@if ($build->subclass) · {{ $build->subclass }}@endif
                </p>
                @if ($build->summary)
                    <p class="mt-1 text-sm text-muted">{{ $build->summary }}</p>
                @endif
            </div>
        </x-card>
    @empty
        <x-empty size="lg" class="col-span-full">
            Non c'è ancora nessuna build consigliata. Le scrivono i dungeon master dal Pannello.
        </x-empty>
    @endforelse
    </div>
</x-pagina>
@endsection
