@extends('layouts.app')
@section('title', 'I miei richiami')

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">

    <x-back dove="sopra" :href="route('profile.edit')">Torna al profilo</x-back>

    <h2 class="flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Warnings" class="h-7 w-7" /> I miei richiami
    </h2>
{{-- Il richiamo attivo resta in evidenza perché modifica il comportamento delle azioni di mercato. --}}
    @if ($activeWarning)
        <x-note tone="danger">
            <span class="font-semibold">Sei sotto richiamo</span> dal
            {{ $activeWarning->created_at->translatedFormat('j F Y') }}.
            Mettere in vendita, comprare da un annuncio, proporre uno scambio e
            accettarne uno passano dal via libera di un dungeon master. Il
            negozio della gilda resta libero.
            <a href="{{ route('market.supervision') }}" class="mt-1 inline-block font-semibold underline">
                Le tue azioni in attesa →
            </a>
        </x-note>
    @endif
{{-- Lo storico resta permanente; l'interfaccia non espone chi ha emesso o revocato il richiamo. --}}
    @forelse ($warnings as $richiamo)
        <x-card @class(['border-on-danger-soft' => $richiamo->isActive()])>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="font-semibold text-fg">
                    Dal {{ $richiamo->created_at->translatedFormat('j F Y') }}
                </span>

                @if ($richiamo->isActive())
                    <x-badge tone="danger">In corso</x-badge>
                @else
                    <x-badge tone="neutral">
                        Tolto il {{ $richiamo->lifted_at->translatedFormat('j F Y') }}
                    </x-badge>
                @endif
            </div>

            <p class="mt-1 text-xs text-muted">
                @if ($richiamo->isActive())
                    Sotto controllo da {{ $richiamo->daysLasted() }}
                    {{ $richiamo->daysLasted() === 1 ? 'giorno' : 'giorni' }}.
                @else
                    È durato {{ $richiamo->daysLasted() }}
                    {{ $richiamo->daysLasted() === 1 ? 'giorno' : 'giorni' }}.
                @endif
            </p>

            <x-inset padding="sm" class="mt-3">
                <p class="text-xs uppercase tracking-wide text-muted">Perché</p>
                <p class="mt-1 text-sm text-fg">{{ $richiamo->reason }}</p>
            </x-inset>

            @if (filled($richiamo->lift_note))
                <x-inset padding="sm" class="mt-2">
                    <p class="text-xs uppercase tracking-wide text-muted">Quando è stato tolto</p>
                    <p class="mt-1 text-sm text-fg">{{ $richiamo->lift_note }}</p>
                </x-inset>
            @endif
        </x-card>
    @empty
        <x-empty size="lg">Non hai mai ricevuto un richiamo.</x-empty>
    @endforelse
</div>
@endsection
