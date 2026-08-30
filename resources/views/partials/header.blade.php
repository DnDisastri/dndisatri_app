@php
    $unread = auth()->user()->unreadNotifications()->count();
    $canPanel = auth()->user()->isDm() || auth()->user()->isAdmin();
@endphp

{{-- backdrop-blur crea uno stacking context: z-40 mantiene l'header sopra al main. --}}
<header class="relative z-40 flex items-center justify-between gap-3 bg-page/90 px-4 py-3 backdrop-blur">
    {{-- Evita un 404 usando un fallback se logo.png manca. --}}
    <a href="{{ route('home') }}" title="{{ config('app.name') }}">
        @if (file_exists(public_path('logo.png')))
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}"
                 class="h-11 w-11 rounded-card object-cover">
        @else
            <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary text-sm font-bold text-on-primary">
                D&D
            </span>
        @endif
    </a>

    <div class="flex items-center gap-3">
        <a href="{{ route('notifications.index') }}" title="Notifiche"
           class="relative flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary transition hover:opacity-90">
            <x-icona :is="\App\Enums\Icon::Notifications" class="h-6 w-6" />

            @if ($unread > 0)
                <span class="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full
                             border-2 border-page bg-active px-1 text-[10px] font-bold text-on-active">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
            @endif
        </a>

        {{-- details gestisce il menu senza JavaScript. --}}
        <details class="relative">
            <summary title="Menù"
                     class="flex h-12 w-12 cursor-pointer list-none items-center justify-center rounded-full
                            bg-active text-on-active transition hover:opacity-90 [&::-webkit-details-marker]:hidden">
                <x-icona :is="\App\Enums\Icon::Menu" class="h-7 w-7" />
            </summary>

            {{-- z-10 basta all'interno dello stacking context dell'header. --}}
            <nav class="absolute right-0 z-10 mt-2 w-52 overflow-hidden rounded-xl border border-line bg-surface shadow-lg shadow-black/10">
                <p class="border-b border-line px-4 py-3 text-sm text-muted">
                    {{ auth()->user()->name }}
                </p>

                @foreach ([
                    ['Gilda', route('guild.index'), \App\Enums\Icon::Guild],
                    ['Build consigliate', route('builds.index'), \App\Enums\Icon::Builds],
                    ['Le mie richieste', route('proposals.index'), \App\Enums\Icon::Proposals],
                    ['Il mio profilo', route('profile.edit'), \App\Enums\Icon::Profile],
                ] as [$voce, $indirizzo, $icona])
                    <a href="{{ $indirizzo }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-fg hover:bg-page">
                        <x-icona :is="$icona" class="h-5 w-5 shrink-0 text-muted" />
                        {{ $voce }}
                    </a>
                @endforeach

                @if ($canPanel)
                    <a href="/admin" class="flex items-center gap-3 border-t border-line px-4 py-2.5 text-sm text-fg hover:bg-page">
                        <x-icona :is="\App\Enums\Icon::Panel" class="h-5 w-5 shrink-0 text-muted" />
                        Pannello
                    </a>
                @endif

                {{-- Auto segue il tema di sistema; app.js aggiorna aria-pressed. --}}
                <div class="border-t border-line px-4 py-3">
                    <p class="mb-2 text-xs uppercase tracking-wide text-muted">Tema</p>

                    <div class="flex gap-1" role="group" aria-label="Tema">
                        @foreach (['auto' => 'Auto', 'light' => 'Chiaro', 'dark' => 'Scuro'] as $valore => $etichetta)
                            <button type="button" data-tema="{{ $valore }}" aria-pressed="false"
                                    class="flex-1 rounded-full border border-line px-2 py-1.5 text-xs font-semibold
                                           text-fg transition hover:border-active
                                           aria-pressed:border-active aria-pressed:bg-active aria-pressed:text-on-active">
                                {{ $etichetta }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-fg hover:bg-page">
                        <x-icona :is="\App\Enums\Icon::Logout" class="h-5 w-5 shrink-0 text-muted" />
                        Esci
                    </button>
                </form>
            </nav>
        </details>
    </div>
</header>
