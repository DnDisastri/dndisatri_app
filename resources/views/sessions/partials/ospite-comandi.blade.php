{{-- I comandi del DM su un ospite: la nota, toglierlo, collegarlo all'account con cui si è registrato. --}}
<div class="mt-1 space-y-1 text-xs">
    @if (filled($ospite->guest_note))
        <p class="text-muted">Nota: {{ $ospite->guest_note }}</p>
    @endif

    <details>
        <summary class="cursor-pointer font-semibold text-active">Gestisci l'ospite</summary>

        <form method="POST" action="{{ route('sessions.guests.link', [$session, $ospite->id]) }}" class="mt-2 flex flex-wrap items-center gap-2">
            @csrf
            <label for="collega-{{ $ospite->id }}" class="sr-only">Si è registrato come</label>
            <select name="user_id" id="collega-{{ $ospite->id }}" required
                    class="min-w-0 max-w-full flex-1 rounded-md border border-line bg-surface px-2 py-1.5 text-sm text-fg">
                <option value="">Si è registrato come…</option>
                @foreach ($candidates as $utente)
                    <option value="{{ $utente->id }}">{{ $utente->name }}</option>
                @endforeach
            </select>
            <x-button variant="quiet" size="sm">Collega</x-button>
        </form>

        @if ($session->acceptsBookings())
            <form method="POST" action="{{ route('sessions.guests.remove', [$session, $ospite->id]) }}" class="mt-2"
                  data-conferma="Togliere {{ $ospite->guest_name }} dalla sessione?">
                @csrf
                <button class="font-semibold text-on-danger-soft hover:underline">Togli l'ospite</button>
            </form>
        @endif
    </details>
</div>
