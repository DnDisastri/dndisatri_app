@php
    /*
     * Da `lg` in su prende il posto di intestazione e barra in basso: in alto
     * logo e notifiche, poi le cinque destinazioni (centro per primo, è la
     * porta principale) e sotto il menù secondario.
     */
    $barra = \App\Support\Navigazione::barra(auth()->user());
    $unread = auth()->user()->unreadNotifications()->count();
    $notifiche = request()->routeIs('notifications.*');
@endphp

<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col gap-4 overflow-y-auto border-r border-line bg-surface p-4 lg:flex">
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 rounded-xl p-1 transition hover:bg-page">
            @if (file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="" class="h-10 w-10 shrink-0 rounded-card object-cover">
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-card bg-primary text-xs font-bold text-on-primary">
                    D&D
                </span>
            @endif
            <span class="truncate font-display text-sm text-fg">{{ config('app.name') }}</span>
        </a>

        <a href="{{ route('notifications.index') }}" title="Notifiche" aria-label="Notifiche"
           @if ($notifiche) aria-current="page" @endif
           @class([
               'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:opacity-90',
               'bg-active text-on-active' => $notifiche,
               'bg-primary text-on-primary' => ! $notifiche,
           ])>
            <x-icona :is="\App\Enums\Icon::Notifications" class="h-5 w-5" />

            @if ($unread > 0)
                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full
                             border-2 border-surface bg-active px-1 text-[10px] font-bold text-on-active">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
            @endif
        </a>
    </div>

    {{-- Lo stesso blocco navy delle pillole in basso, in verticale. --}}
    <nav class="flex flex-col gap-1 rounded-card bg-primary p-1.5" aria-label="Menù laterale">
        @foreach ([$barra['centro'], ...$barra['sinistra'], ...$barra['destra']] as $voce)
            <a @if ($voce['href']) href="{{ $voce['href'] }}" @endif
               @if ($voce['attiva']) aria-current="page" @endif
               @class([
                   'flex items-center gap-3 rounded-full px-3 py-2 text-sm font-semibold transition',
                   'bg-active text-on-active' => $voce['attiva'],
                   'text-on-primary-soft hover:text-on-primary' => $voce['href'] && ! $voce['attiva'],
                   'text-on-primary-soft opacity-40 cursor-default' => ! $voce['href'],
               ])>
                <x-icona :is="$voce['icona']" class="h-5 w-5 shrink-0" />
                {{ $voce['nome'] }}
            </a>
        @endforeach
    </nav>

    <div class="flex flex-col">
        @include('partials.menu', ['laterale' => true])
    </div>

    <p class="mt-auto truncate px-3 text-xs text-muted">{{ auth()->user()->name }}</p>
</aside>
