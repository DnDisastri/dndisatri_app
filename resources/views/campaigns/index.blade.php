@extends('layouts.app')
@section('title', 'Campagne')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Campaigns" class="h-7 w-7" /> Campagne
    </h2>
    <p class="mb-6 text-sm text-muted">Mondi da esplorare, avventure da vivere e leggende da scrivere. Scegli la tua prossima campagna.</p>

    @if (count($seasons) > 1)
        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('campaigns.index') }}"
               @class([
                   'rounded-full px-4 py-1.5 text-sm font-semibold transition',
                   'bg-active text-on-active' => $season === null,
                   'bg-surface text-fg border border-line hover:border-active' => $season !== null,
               ])>Tutte</a>

            @foreach ($seasons as $numero)
                <a href="{{ route('campaigns.index', ['season' => $numero]) }}"
                   @class([
                       'rounded-full px-4 py-1.5 text-sm font-semibold transition',
                       'bg-active text-on-active' => $season === $numero,
                       'bg-surface text-fg border border-line hover:border-active' => $season !== $numero,
                   ])>Season {{ $numero }}</a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 gap-1">
        @forelse ($campaigns as $campaign)

            <x-poster variant="tile"
                      :href="route('campaigns.show', $campaign)"
                      :image="$campaign->coverUrl()"
                      :label="$campaign->isActive() ? null : 'Conclusa'"
                      :title="$campaign->title"
                      action="" />
        @empty
            <x-empty size="lg" class="col-span-full">
                @if ($season !== null)
                    Nessuna campagna nella season {{ $season }}.
                @else
                    Non c'è ancora nessuna campagna.
                @endif
            </x-empty>
        @endforelse
    </div>
</div>
@endsection
