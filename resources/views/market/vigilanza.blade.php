@extends('layouts.app')
@section('title', 'In attesa di via libera')

@php
    use App\Enums\PendingChangeStatus;
// Associa lo stato dell'azione al tono visuale usato nelle card di vigilanza.
    $tinta = fn (PendingChangeStatus $s) => match ($s) {
        PendingChangeStatus::Pending => 'accent',
        PendingChangeStatus::Approved => 'own',
        PendingChangeStatus::Rejected => 'danger',
    };
@endphp

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">

    <x-back dove="sopra" :href="route('market.trades')">Torna al mercato</x-back>

    <h2 class="flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Trades" class="h-7 w-7" /> In attesa di via libera
    </h2>

    @if ($sottoRichiamo)
        <x-note tone="danger">
            <span class="font-semibold">Sei sotto richiamo.</span>
            Mettere in vendita, comprare da un annuncio, proporre uno scambio e
            accettarne uno passano dal via libera di un dungeon master prima di
            succedere davvero. Il negozio della gilda resta libero.
        </x-note>
    @else
        <x-note>
            Il richiamo è stato tolto: le tue azioni non passano più dal
            controllo. Qui sotto resta quello che è passato di lì.
        </x-note>
    @endif

    <section class="space-y-3">
        <h3 class="text-sm font-bold uppercase tracking-wide text-muted">In attesa</h3>

        @forelse ($inAttesa as $azione)
            <x-card>
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-semibold text-fg">{{ $azione->type->label() }}</span>
                    <x-badge :tone="$tinta($azione->status)">{{ $azione->status->label() }}</x-badge>
                </div>

                <x-inset padding="sm">
                    <dl class="divide-y divide-line">
                        @foreach ($azione->details() as $riga)
                            <div class="flex flex-wrap gap-x-4 gap-y-1 py-1.5 first:pt-0 last:pb-0">
                                <dt class="w-32 shrink-0 text-sm text-muted">{{ $riga['voce'] }}</dt>
                                <dd class="text-sm text-fg">{{ $riga['valore'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-inset>

                <p class="mt-3 text-xs text-muted">
                    Chiesta {{ $azione->created_at->diffForHumans() }}. Aspetta che un DM la guardi.
                </p>
            </x-card>
        @empty
            <x-empty>Non hai niente in attesa. Quello che chiedi al mercato compare qui finché un DM non decide.</x-empty>
        @endforelse
    </section>
{{-- Lo storico delle decisioni compare solo quando esistono azioni già revisionate. --}}
    @if ($decise->isNotEmpty())
        <section class="space-y-3">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Già decise</h3>

            @foreach ($decise as $azione)
                <x-card @class(['opacity-90'])>
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <span class="font-semibold text-fg">{{ $azione->type->label() }}</span>
                        <x-badge :tone="$tinta($azione->status)">{{ $azione->status->label() }}</x-badge>
                    </div>

                    <x-inset padding="sm">
                        <dl class="divide-y divide-line">
                            @foreach ($azione->details() as $riga)
                                <div class="flex flex-wrap gap-x-4 gap-y-1 py-1.5 first:pt-0 last:pb-0">
                                    <dt class="w-32 shrink-0 text-sm text-muted">{{ $riga['voce'] }}</dt>
                                    <dd class="text-sm text-fg">{{ $riga['valore'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-inset>
{{-- Per le azioni rifiutate il motivo del DM è parte essenziale della decisione e viene sempre mostrato quando disponibile. --}}
                    @if ($azione->status === PendingChangeStatus::Rejected && filled($azione->review_note))
                        <div class="mt-3 rounded-md border border-line bg-danger-soft px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-on-danger-soft">Perché è stata bloccata</p>
                            <p class="mt-1 text-sm text-on-danger-soft">{{ $azione->review_note }}</p>
                        </div>
                    @endif

                    <p class="mt-3 text-xs text-muted">
                        @if ($azione->status === PendingChangeStatus::Approved)
                            Eseguita {{ $azione->reviewed_at?->diffForHumans() }}.
                        @else
                            Decisa {{ $azione->reviewed_at?->diffForHumans() }}. Puoi riproporla dal mercato.
                        @endif
                    </p>
                </x-card>
            @endforeach
        </section>
    @endif
</div>
@endsection
