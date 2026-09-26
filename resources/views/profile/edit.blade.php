@extends('layouts.app')
@section('title', 'Il mio profilo')

@section('content')
<div class="mx-auto max-w-2xl space-y-6 px-4 py-6">
    <h2 class="flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Profile" class="h-7 w-7" /> Il mio profilo
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

    <x-panel title="Dati personali">
        <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
            @csrf
            @method('PATCH')

            <x-field name="name" label="Nome utente" :value="$user->name" autocomplete="username" required />
            <x-field name="email" label="Email" type="email" :value="$user->email" autocomplete="email" required />

            <p class="text-xs text-muted">
                Il nome è quello con cui ti vedono gli altri nella Gilda, ed è
                di uno solo: se è già preso, te lo dice qui sotto.
            </p>

            <x-button class="self-start">Salva</x-button>
        </form>
    </x-panel>

    <x-panel title="Password">
        <form method="POST" action="{{ route('profile.password') }}" class="flex flex-col gap-4">
            @csrf
            @method('PUT')
{{-- Richiede la password attuale anche con una sessione autenticata, per evitare modifiche da sessioni lasciate aperte. --}}
            <x-field name="current_password" label="Password attuale" type="password"
                     autocomplete="current-password" required />

            <x-field name="password" label="Nuova password" type="password"
                     autocomplete="new-password" required />
            <x-field name="password_confirmation" label="Ripeti la nuova password" type="password"
                     autocomplete="new-password" required />

            <p class="text-xs text-muted">Almeno otto caratteri.</p>

            <x-button class="self-start">Cambia password</x-button>
        </form>
    </x-panel>

    <x-panel title="I miei eroi">
        <div class="space-y-2">
            @forelse ($characters as $character)
                <x-card padding="sm" :href="route('characters.show', $character)"
                        class="flex flex-wrap items-baseline justify-between gap-2">
                    <span >
                        <span class="font-semibold text-fg">{{ $character->name }}</span>
                        <span class="text-sm text-muted">
                            · {{ $character->race }} · {{ $character->class }} · liv. {{ $character->level }}
                        </span>
                    </span>
                    <x-grado :level="$character->level" />

                    @unless ($character->isAlive())
                        <x-badge tone="danger">
                            Caduto il {{ $character->died_at->translatedFormat('j F Y') }}
                        </x-badge>
                    @endunless
                </x-card>
            @empty
                <x-empty>Non hai ancora un personaggio.</x-empty>
            @endforelse
        </div>

        <p class="mt-3 text-center">
            <a href="{{ route('characters.index') }}" class=" flex w-full justify-center text-sm text-muted hover:underline">
                Vai ai miei eroi  <x-icona :is="\App\Enums\Icon::GoTo" class="h-5 w-5" />
            </a>
        </p>
    </x-panel>
{{-- Mostra lo storico solo a chi ha ricevuto almeno un richiamo. --}}
    @if ($warningHistory['count'] > 0)
        <x-panel title="I miei richiami">
            <p class="text-sm text-muted">
                Ne hai ricevuti <span class="font-semibold text-fg">{{ $warningHistory['count'] }}</span>,
                per un totale di <span class="font-semibold text-fg">{{ $warningHistory['days'] }}</span>
                {{ $warningHistory['days'] === 1 ? 'giorno' : 'giorni' }} sotto controllo.
            </p>
            <p class="mt-2 text-xs text-muted">
                Lo storico non si cancella quando un richiamo viene tolto: è il
                punto del meccanismo.
            </p>
            <p class="mt-3">
                <a href="{{ route('profile.warnings') }}" class="text-sm text-muted hover:underline">
                    Vedi lo storico →
                </a>
            </p>
        </x-panel>
    @endif
</div>
@endsection
