@php
    use App\Enums\Icon;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    /*
     * La barra di navigazione (D21): cinque destinazioni fisse, sempre visibile.
     *
     * Non sono cinque pillole in fila ma **tre blocchi**: due coppie e un
     * cerchio in mezzo. Raggruppare due voci sotto un unico fondo le lega —
     * Campagne e Libro Mastro parlano delle storie, Mercato ed Eventi delle
     * cose che si fanno — e lascia al centro lo spazio per stare più grande
     * senza schiacciare i vicini.
     *
     * Eroi sta al centro ed è più grande perché il personaggio è il
     * cuore dell'applicazione. **Non è più rosso di suo**: il rosso adesso
     * vuol dire una cosa sola, «sei qui», e usarlo anche per dire «questo
     * conta» lo renderebbe illeggibile in tutti e due i sensi.
     *
     * Le voci che non hanno ancora una pagina restano spente: si vedono, si
     * capisce che esistono, e non portano da nessuna parte.
     */
    $storie = [
        ['nome' => 'Campagne', 'rotta' => 'campaigns.index', 'icona' => Icon::Campaigns],
        ['nome' => 'Libro Mastro', 'rotta' => 'ledger.index', 'icona' => Icon::Ledger],
    ];

    // «Eroi» e non «Personaggi»: è lo stesso posto che la pagina chiama «I miei
    // eroi», e due nomi per la stessa porta sono un nome di troppo.
    $centro = ['nome' => 'Eroi', 'rotta' => 'characters.index', 'icona' => Icon::Characters];

    $cose = [
        ['nome' => 'Mercato', 'rotta' => 'market.index', 'icona' => Icon::Market],
        ['nome' => 'Eventi', 'rotta' => 'events.index', 'icona' => Icon::Events],
    ];

    /*
     * Il cerchio centrale si accende sui **miei** eroi, non su un eroe
     * qualunque.
     *
     * `characters.*` prende anche la scheda di un altro, e lì il rosso diceva
     * una bugia: «sei nella tua sezione», mentre si sta guardando il
     * personaggio di qualcun altro — una pagina che dai miei eroi non si
     * raggiunge nemmeno. Ci si arriva dalla Gilda, che nella barra non c'è, e
     * quindi la risposta giusta è quella che la barra dà già per la Gilda: non
     * si accende niente.
     *
     * Senza personaggio nell'indirizzo — l'elenco, la creazione — resta acceso.
     */
    $mio = function (): bool {
        $pg = request()->route('character');

        return ! $pg instanceof App\Models\Character || $pg->user_id === auth()->id();
    };

    // Una voce è attiva se ci sei dentro, anche in una sua pagina di
    // dettaglio: `campaigns.show` accende Campagne come `campaigns.index`.
    $stato = function (array $voce): array {
        $esiste = Route::has($voce['rotta']);

        return [
            $esiste,
            $esiste && request()->routeIs(Str::before($voce['rotta'], '.').'.*'),
            $esiste ? route($voce['rotta']) : null,
        ];
    };
@endphp

{{-- Il padding orizzontale del `nav` è il margine dai bordi dello schermo.

     `z-30` e non più `z-40`: la barra è arredamento fisso, e un menù che uno ha
     appena aperto le passa sopra. Prima erano pari, e una tendina che scendeva
     fin quaggiù finiva tagliata dalle pillole. --}}
<nav class="pointer-events-none fixed inset-x-0 bottom-0 z-30 px-6 pb-3" aria-label="Navigazione principale">
    <div class="pointer-events-auto flex w-full items-center gap-3">

        @foreach ([$storie, null, $cose] as $gruppo)
            @if ($gruppo === null)
                {{-- Il cerchio centrale, fra le due coppie. --}}
                @php
                    [$esiste, $attiva, $indirizzo] = $stato($centro);

                    // …e sulla scheda di un altro non si accende: vedi `$mio`.
                    $attiva = $attiva && $mio();
                @endphp

                <a @if ($esiste) href="{{ $indirizzo }}" @endif
                   title="{{ $centro['nome'] }}" aria-label="{{ $centro['nome'] }}"
                   @if ($attiva) aria-current="page" @endif
                   @class([
                       'flex h-16 w-16 shrink-0 items-center justify-center rounded-full shadow-lg shadow-black/20 transition',
                       'bg-active text-on-active' => $attiva,
                       // Lo **stesso** colore delle icone laterali, non quello
                       // pieno: il fondo navy è lo stesso, e due chiari diversi
                       // sopra lo stesso fondo si notano subito. Il cerchio
                       // centrale conta già di suo perché è più grande — non
                       // gli serve anche un'icona più accesa, che è la stessa
                       // ragione per cui non è rosso.
                       'bg-primary text-on-primary-soft hover:text-on-primary' => $esiste && ! $attiva,
                       'bg-off text-off-fg cursor-default' => ! $esiste,
                   ])>
                    <x-icona :is="$centro['icona']" class="h-7 w-7" />
                </a>
            @else
                {{-- Il fondo sta sul gruppo, non sulle singole voci: dentro,
                     ogni voce è solo un'icona finché non diventa quella
                     attiva, e allora si accende del suo cerchio rosso. --}}
                {{-- `flex-1` è quello che li fa allargare: la pillola non
                     cresce da sola perché dentro ha solo icone di misura
                     fissa, e senza istruzioni si stringe attorno a loro.
                     I due gruppi si dividono in parti uguali quello che
                     avanza dopo il cerchio centrale, che resta com'è.

                     `justify-between` e non `justify-around`: il secondo
                     distribuisce lo spazio *attorno* a ogni icona, e ai lati
                     esterni ne lascia metà di quello che mette in mezzo — il
                     cerchio rosso dell'attiva finiva staccato dal bordo di una
                     misura diversa da sopra e sotto. Così invece le due icone
                     vanno agli estremi e restano incorniciate dal `p-1.5` in
                     tutte le direzioni: stesso margine sopra, sotto e di lato. --}}
                <div class="flex flex-1 items-center justify-between rounded-full bg-primary p-1.5 shadow-lg shadow-black/20">
                    @foreach ($gruppo as $voce)
                        @php [$esiste, $attiva, $indirizzo] = $stato($voce); @endphp

                        <a @if ($esiste) href="{{ $indirizzo }}" @endif
                           title="{{ $voce['nome'] }}" aria-label="{{ $voce['nome'] }}"
                           @if ($attiva) aria-current="page" @endif
                           @class([
                               // `shrink-0`: su uno schermo stretto il cerchio
                               // dell'icona attiva si schiaccerebbe in un ovale.
                               'flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition',
                               'bg-active text-on-active' => $attiva,
                               'text-on-primary-soft hover:text-on-primary' => $esiste && ! $attiva,
                               'text-on-primary-soft opacity-40 cursor-default' => ! $esiste,
                           ])>
                            <x-icona :is="$voce['icona']" class="h-6 w-6" />
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</nav>
