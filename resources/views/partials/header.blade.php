@php
    $unread = auth()->user()->unreadNotifications()->count();
    $canPanel = auth()->user()->isDm() || auth()->user()->isAdmin();
@endphp

{{--
    L'intestazione del mockup: il logo a sinistra, due cerchi a destra.

    Non ha un fondo suo — è la pagina che si vede sotto — perché con la barra
    di navigazione in basso il peso visivo sta lì, e due strisce colorate una
    sopra e una sotto stringerebbero il contenuto.

    Il resto delle voci sta nel menù `⋯`: in cima ci arrivano solo le due cose
    che si guardano decine di volte al giorno.

    `relative z-40` non è ornamentale, ed è il seguito di `backdrop-blur`: la
    sfocatura **crea un contesto di impilamento**, e senza uno z qui lo `z-50`
    del menù resterebbe prigioniero là dentro. Non competerebbe più con la
    pagina ma solo con gli altri figli dell'intestazione — e siccome
    l'intestazione viene prima di `<main>` nel documento, tutto il contenuto le
    verrebbe disegnato sopra. Il menù finiva sotto le card e sotto le
    locandine: non perché perdesse, ma perché venivano dopo.
--}}
<header class="relative z-40 flex items-center justify-between gap-3 bg-page/90 px-4 py-3 backdrop-blur">
    {{-- Il logo vero non c'è ancora: finché manca il file, al suo posto sta
         un segnaposto. Deciso qui e non nel browser, così non si sporca la
         console con un 404 a ogni pagina. --}}
    <a href="{{ route('home') }}" title="{{ config('app.name') }}">
        @if (file_exists(public_path('logo.png')))
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}"
                 class="h-11 w-11 rounded-full object-cover">
        @else
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">
                D&D
            </span>
        @endif
    </a>

    <div class="flex items-center gap-3">
        <a href="{{ route('notifications.index') }}" title="Notifiche"
           class="relative flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary transition hover:opacity-90">
            <x-icona :is="\App\Enums\Icon::Notifications" class="h-6 w-6" />

            {{-- Il pallino è tutto il meccanismo: dice che c'è qualcosa di
                 nuovo e sparisce quando la pagina è stata aperta. Il bordo lo
                 stacca dal cerchio che ha sotto, o si confonderebbe. --}}
            @if ($unread > 0)
                <span class="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full
                             border-2 border-page bg-active px-1 text-[10px] font-bold text-on-active">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
            @endif
        </a>

        {{-- Il menù di tutto il resto. `details` fa da solo apertura e
             chiusura: nessun javascript da mantenere per un elenco di link. --}}
        <details class="relative">
            <summary title="Menù"
                     class="flex h-12 w-12 cursor-pointer list-none items-center justify-center rounded-full
                            bg-active text-on-active transition hover:opacity-90 [&::-webkit-details-marker]:hidden">
                <x-icona :is="\App\Enums\Icon::Menu" class="h-7 w-7" />
            </summary>

            {{-- `z-10` e non `z-50`: qui dentro basta stare sopra ai due
                 cerchi, perché a portare tutta l'intestazione sopra la pagina
                 ci pensa lo z sul tag qui sopra. Un `z-50` a questo punto
                 sembrerebbe fare qualcosa e non farebbe niente. --}}
            <nav class="absolute right-0 z-10 mt-2 w-52 overflow-hidden rounded-xl border border-line bg-surface shadow-lg shadow-black/10">
                <p class="border-b border-line px-4 py-3 text-sm text-muted">
                    {{ auth()->user()->name }}
                </p>

                {{-- Ogni voce col suo segno: l'occhio ci arriva prima che la
                     parola si legga. «Caduti» non c'è più — i caduti stanno
                     nella Gilda, con i vivi (P13, P15b), e un secondo ingresso
                     alla stessa pagina era una riga in più che non portava da
                     nessuna parte di diverso. --}}
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

                {{-- Il tema. Tre stati e non un interruttore a due: «automatico»
                     segue il telefono, che di sera passa a scuro da solo, ed è
                     la scelta giusta per quasi tutti. Chiaro e scuro servono a
                     chi quel comportamento non lo vuole.

                     Sono tre pulsanti in fila e non una tendina perché lo stato
                     attuale si deve **vedere**, non aprire. Li accende
                     `resources/js/app.js` con `aria-pressed`, che dice la stessa
                     cosa a chi guarda e a chi ascolta. --}}
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
