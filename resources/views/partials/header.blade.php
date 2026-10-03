@php
    $unread = auth()->user()->unreadNotifications()->count();
@endphp

{{-- backdrop-blur crea uno stacking context: z-40 mantiene l'header sopra al main. --}}
{{-- Da `lg` in su la sostituisce la barra laterale. --}}
<header class="relative z-40 flex items-center justify-between gap-3 bg-page/90 px-4 py-3 backdrop-blur md:px-6 lg:hidden">
    {{-- Evita un 404 usando un fallback se logo.png manca. --}}
    <a href="{{ route('home') }}" title="{{ config('app.name') }}" class="relative block h-11 w-11">
        @if (file_exists(public_path('logo.png')))
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}"
                 class="h-11 w-11 rounded-card object-cover">
        @else
            <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary text-sm font-bold text-on-primary">
                D&D
            </span>
        @endif

        {{-- L'icona casa porta alla Home (solo da loggati). --}}
        <span class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <x-icona :is="\App\Enums\Icon::Home" class="h-7 w-7 text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.7)]" />
        </span>
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
        <details class="relative" data-tendina>
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

                @include('partials.menu')
            </nav>
        </details>
    </div>
</header>
