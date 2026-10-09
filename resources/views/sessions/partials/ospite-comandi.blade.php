{{-- I comandi del DM su un ospite: la nota, toglierlo, collegarlo all'account con cui si è registrato. --}}
@php
    // Si è registrato con la stessa email: il collegamento si propone da solo.
    $stessaEmail = filled($ospite->guest_email)
        ? $candidates->firstWhere('email', $ospite->guest_email)
        : null;
@endphp

<div class="mt-1 space-y-1 text-xs">
    @if (filled($ospite->guest_note))
        <p class="text-muted">Nota: {{ $ospite->guest_note }}</p>
    @endif

    @if ($stessaEmail)
        <form method="POST" action="{{ route('sessions.guests.link', [$session, $ospite->id]) }}"
              class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="hidden" name="user_name" value="{{ $stessaEmail->name }}">
            <span class="text-fg">Si è registrato come <strong>{{ $stessaEmail->name }}</strong>, con la stessa email.</span>
            <x-button variant="secondary" size="sm">Collega</x-button>
        </form>
    @endif

    <details>
        <summary class="cursor-pointer font-semibold text-active">Gestisci l'ospite</summary>

        <form method="POST" action="{{ route('sessions.guests.link', [$session, $ospite->id]) }}" class="mt-2 flex flex-wrap items-center gap-2">
            @csrf
            <label for="collega-{{ $ospite->id }}" class="sr-only">Si è registrato come</label>
            {{-- Campo con suggerimenti: con decine di giocatori una tendina non si scorre. --}}
            <input type="text" name="user_name" id="collega-{{ $ospite->id }}" list="giocatori-registrati" required
                   placeholder="Si è registrato come…" autocomplete="off"
                   class="min-w-0 max-w-full flex-1 rounded-md border border-line bg-surface px-2 py-1.5 text-sm text-fg">
            <x-button variant="quiet" size="sm">Collega</x-button>
        </form>

        @if ($session->acceptsBookings())
            <form method="POST" action="{{ route('sessions.guests.remove', [$session, $ospite->id]) }}" class="mt-2"
                  data-conferma="Togliere {{ $ospite->guest_name }} dalla sessione?" data-conferma-azione="Togli">
                @csrf
                <button class="font-semibold text-on-danger-soft hover:underline">Togli l'ospite</button>
            </form>
        @endif
    </details>
</div>

@once
    <datalist id="giocatori-registrati">
        @foreach ($candidates as $utente)
            <option value="{{ $utente->name }}"></option>
        @endforeach
    </datalist>
@endonce
