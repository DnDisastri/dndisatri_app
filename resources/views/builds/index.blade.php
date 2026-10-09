@extends('layouts.app')
@section('title', 'Build consigliate')

@section('content')
<x-pagina>
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Builds" class="h-7 w-7" /> Build consigliate
    </h2>
    <x-intro :page="\App\Enums\IntroPage::Builds" class="mb-6" />

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
    @forelse ($builds as $build)
        <x-card :href="route('builds.show', $build)" flush padding="none">
            {{-- La fascia c'è sempre: la pillola resta nello stesso punto anche senza copertina. --}}
            <div class="relative h-24">
                @if ($build->coverUrl())
                    <img src="{{ $build->coverUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center bg-page">
                        <x-icona :is="\App\Enums\Icon::Builds" class="h-8 w-8 text-muted" />
                    </span>
                @endif

                @if ($build->tag)
                    <span class="absolute left-2 top-2"><x-badge tone="accent">{{ $build->tag }}</x-badge></span>
                @endif
            </div>

            <div class="p-4">
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

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>
@endsection
