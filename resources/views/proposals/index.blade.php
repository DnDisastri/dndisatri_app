@extends('layouts.app')
@section('title', 'Le mie richieste')

@php use App\Enums\PendingChangeStatus; @endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="\App\Enums\Icon::Proposals" class="h-7 w-7" />
            {{ $mostraArchiviate ? 'Richieste archiviate' : 'Le mie richieste' }}
        </h2>

        <div class="flex flex-wrap items-center gap-2">
            @if ($mostraArchiviate)
                <x-button variant="quiet" size="sm" :href="route('proposals.index')">
                    <x-icona :is="\App\Enums\Icon::Back" class="h-4 w-4" /> Torna alle attive
                </x-button>
            @else
                @if ($daSvuotare > 0)

                    <form method="POST" action="{{ route('proposals.clear') }}">
                        @csrf
                        <x-button variant="quiet" size="sm">
                            <x-icona :is="\App\Enums\Icon::Stash" class="h-4 w-4" /> Svuota le decise
                        </x-button>
                    </form>
                @endif

                @if ($archiviate > 0)
                    <x-button variant="quiet" size="sm" :href="route('proposals.index', ['archiviate' => 1])">
                        Archiviate ({{ $archiviate }})
                    </x-button>
                @endif
            @endif
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($changes as $change)
            @php
                $colour = match ($change->status) {
                    PendingChangeStatus::Pending => 'text-on-accent-soft',
                    PendingChangeStatus::Approved => 'text-primary',
                    PendingChangeStatus::Rejected => 'text-on-danger-soft',
                };
            @endphp

            <x-panel class="relative">

                @if ($mostraArchiviate)
                    <form method="POST" action="{{ route('proposals.restore', $change) }}" class="absolute right-2 top-2">
                        @csrf
                        <button type="submit" title="Rimetti in lista"
                                class="rounded-full p-1 text-muted transition hover:bg-page hover:text-fg">
                            <x-icona :is="\App\Enums\Icon::Unstash" class="h-5 w-5" />
                            <span class="sr-only">Ripristina</span>
                        </button>
                    </form>
                @elseif ($change->isArchivable())
                    <form method="POST" action="{{ route('proposals.archive', $change) }}" class="absolute right-2 top-2">
                        @csrf
                        <button type="submit" title="Archivia"
                                class="rounded-full p-1 text-muted transition hover:bg-page hover:text-fg">
                            <x-icona :is="\App\Enums\Icon::Stash" class="h-5 w-5" />
                            <span class="sr-only">Archivia</span>
                        </button>
                    </form>
                @endif

                <div class="flex flex-wrap items-baseline justify-between gap-2 pr-9">
                    <div>
                        <span class="rounded bg-page px-2 py-0.5 text-xs text-muted">
                            {{ $change->type->label() }}
                        </span>
                        <span class="ml-2 font-semibold">{{ $change->character?->name }}</span>
                    </div>

                    <span class="text-sm font-semibold {{ $colour }}">{{ $change->status->label() }}</span>
                </div>

                @if ($change->summary)
                    <p class="mt-2 text-sm text-muted">{{ $change->summary }}</p>
                @endif

                <p class="mt-2 text-xs text-muted">
                    Mandata {{ $change->created_at->diffForHumans() }}
                    @if ($change->reviewed_at)

                        · decisa {{ $change->reviewed_at->diffForHumans() }}
                    @endif
                </p>

                @if ($change->review_note)
                    <p class="mt-2 rounded bg-page px-3 py-2 text-sm text-muted">
                        «{{ $change->review_note }}»
                    </p>
                @endif
            </x-panel>
        @empty
            <p class="text-center text-muted">
                {{ $mostraArchiviate
                    ? 'Nessuna richiesta archiviata.'
                    : 'Non hai ancora mandato nessuna richiesta.' }}
            </p>
        @endforelse
    </div>
</div>
@endsection
