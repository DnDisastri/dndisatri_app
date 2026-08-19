@php
    use App\Enums\Icon;

    /*
     * La barra di navigazione da DM.
     *
     * Stessa forma di quella del giocatore — due coppie e un cerchio in mezzo —
     * ma **destinazioni diverse**, ed è tutto il punto: un DM non apre l'app per
     * i suoi eroi, la apre per la sua serata e il suo tavolo.
     *
     *   Campagne · Serate   —(Regia)—   Gilda · Scrivania
     *
     * Il cerchio centrale è la **Regia**: la home di chi conduce, la serata in
     * cima e il tavolo sotto. La «Scrivania» è la porta del Pannello (D20), dove
     * restano le decisioni pesanti — l'unica voce che esce dall'app, e per
     * questo l'ultima.
     *
     * Il rosso vuol dire una cosa sola, «sei qui». La Scrivania non si accende
     * mai: è un link esterno all'applicazione, non una pagina che si «abita».
     */
    $sinistra = [
        ['nome' => 'Campagne', 'href' => route('campaigns.index'), 'icona' => Icon::Campaigns, 'attiva' => request()->routeIs('campaigns.*')],
        ['nome' => 'Serate', 'href' => route('sessions.index'), 'icona' => Icon::Sessions, 'attiva' => request()->routeIs('sessions.*')],
    ];

    $destra = [
        ['nome' => 'Gilda', 'href' => route('guild.index'), 'icona' => Icon::Guild, 'attiva' => request()->routeIs('guild.*') || request()->routeIs('fallen.*')],
        ['nome' => 'Scrivania', 'href' => '/admin', 'icona' => Icon::Panel, 'attiva' => false],
    ];

    $regiaAttiva = request()->routeIs('dm.*');
@endphp

<nav class="pointer-events-none fixed inset-x-0 bottom-0 z-30 px-6 pb-3" aria-label="Navigazione DM">
    <div class="pointer-events-auto flex w-full items-center gap-3">

        {{-- Coppia di sinistra: le storie e il loro calendario. --}}
        <div class="flex flex-1 items-center justify-between rounded-full bg-primary p-1.5 shadow-lg shadow-black/20">
            @foreach ($sinistra as $voce)
                <a href="{{ $voce['href'] }}"
                   title="{{ $voce['nome'] }}" aria-label="{{ $voce['nome'] }}"
                   @if ($voce['attiva']) aria-current="page" @endif
                   @class([
                       'flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition',
                       'bg-active text-on-active' => $voce['attiva'],
                       'text-on-primary-soft hover:text-on-primary' => ! $voce['attiva'],
                   ])>
                    <x-icona :is="$voce['icona']" class="h-6 w-6" />
                </a>
            @endforeach
        </div>

        {{-- Il cerchio centrale: la Regia. Più grande, perché condurre è il
             mestiere di chi apre quest'app. --}}
        <a href="{{ route('dm.home') }}" title="Regia" aria-label="Regia"
           @if ($regiaAttiva) aria-current="page" @endif
           @class([
               'flex h-16 w-16 shrink-0 items-center justify-center rounded-full shadow-lg shadow-black/20 transition',
               'bg-active text-on-active' => $regiaAttiva,
               'bg-primary text-on-primary-soft hover:text-on-primary' => ! $regiaAttiva,
           ])>
            <x-icona :is="Icon::DmRequests" class="h-7 w-7" />
        </a>

        {{-- Coppia di destra: le persone e la scrivania. --}}
        <div class="flex flex-1 items-center justify-between rounded-full bg-primary p-1.5 shadow-lg shadow-black/20">
            @foreach ($destra as $voce)
                <a href="{{ $voce['href'] }}"
                   title="{{ $voce['nome'] }}" aria-label="{{ $voce['nome'] }}"
                   @if ($voce['attiva']) aria-current="page" @endif
                   @class([
                       'flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition',
                       'bg-active text-on-active' => $voce['attiva'],
                       'text-on-primary-soft hover:text-on-primary' => ! $voce['attiva'],
                   ])>
                    <x-icona :is="$voce['icona']" class="h-6 w-6" />
                </a>
            @endforeach
        </div>
    </div>
</nav>
