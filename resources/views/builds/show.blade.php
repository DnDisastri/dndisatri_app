@extends('layouts.app')
@section('title', $build->title)

@section('content')
<div class="mx-auto max-w-3xl space-y-4 px-4 py-6">

    <div class="relative flex min-h-36 items-end overflow-hidden rounded-card bg-primary p-4">
        @if ($build->coverUrl())
        {{-- La copertina è decorativa: il titolo della build è già presente come testo. --}}
            <img src="{{ $build->coverUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/10"></div>
        @endif
        <h2 class="relative text-2xl text-white drop-shadow">{{ $build->title }}</h2>
    </div>

    <div class="flex flex-wrap gap-1">
        @if ($build->tag)
            <x-badge tone="accent">{{ $build->tag }}</x-badge>
        @endif
        <x-badge>{{ $build->class }}@if ($build->subclass) · {{ $build->subclass }}@endif</x-badge>
    </div>

    @if ($build->summary)
        <p class="text-sm text-fg">{{ $build->summary }}</p>
    @endif

    @if ($build->abilities_advice)
        <x-panel title="Su cosa puntare">
            <p class="text-sm text-muted">{{ $build->abilities_advice }}</p>
        </x-panel>
    @endif

    @if (filled($build->scores))
        <x-panel title="Caratteristiche">
            <div class="flex flex-wrap gap-1">
                @foreach ($build->scores as $abilita => $valore)
                    <x-badge>{{ \App\Domain\Dnd\Ability::from($abilita)->fullName() }} {{ $valore }}</x-badge>
                @endforeach
            </div>
        </x-panel>
    @endif

    @if (filled($build->skills))
        <x-panel title="Abilità">
            <div class="flex flex-wrap gap-1">
                @foreach ($build->skills as $s)
                    <x-badge>{{ config("dnd.character.skill_names.$s", $s) }}</x-badge>
                @endforeach
            </div>
        </x-panel>
    @endif

    @if (config("dnd.classes.list.{$build->class}.equip"))
        <x-panel title="Equipaggiamento">
            <p class="text-sm text-muted">{{ config("dnd.classes.list.{$build->class}.equip") }}</p>
        </x-panel>
    @endif

    @if ($build->progression)
        <x-panel title="Come cresce">
            <p class="text-sm text-muted">{{ $build->progression }}</p>
        </x-panel>
    @endif
{{-- Escape prima di `nl2br()` per conservare gli a capo senza renderizzare HTML arbitrario. --}}
    @if ($build->body)
        <x-panel title="In dettaglio">
            <p class="text-sm text-muted">{!! nl2br(e($build->body)) !!}</p>
        </x-panel>
    @endif

    <div class="pt-2">
        @can('create', \App\Models\Character::class)
            <x-button size="lg" full variant="secondary"
                      :href="route('characters.create', ['build' => $build->slug])">
                Usa questa build per il tuo personaggio
            </x-button>
        @else
            <x-button size="lg" full variant="secondary" disabled>
                Usa questa build per il tuo personaggio
            </x-button>
            <p class="mt-2 text-center text-xs text-muted">
                Per ora si tiene un personaggio alla volta: hai già il tuo. La build resta qui da sfogliare.
            </p>
        @endcan
    </div>

    <x-back dove="sotto" :href="route('builds.index')">Torna alle build</x-back>
</div>
@endsection
