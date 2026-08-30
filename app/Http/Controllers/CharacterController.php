<?php

namespace App\Http\Controllers;

use App\Enums\LedgerAction;
use App\Enums\SheetSection;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\PendingChange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharacterController extends Controller
{
    /**
     * I miei eroi (P42): la voce centrale della barra.
     *
     * Non è un elenco di personaggi — quello è la Gilda. È il posto da cui si
     * entra e si va dove serve: la scheda, le quattro proposte, il registro.
     *
     * Un DM può averne più d'uno, quindi si ragiona sempre su una collezione
     * anche quando è di uno solo.
     */
    public function index(Request $request): View
    {
        $characters = $request->user()->characters()
            ->with('classes')
            ->orderByDesc('died_at')
            ->orderBy('name')
            ->get();

        return view('characters.index', [
            'characters' => $characters,

            // Il riquadro in fondo: le richieste ancora da decidere, e le
            // ultime decise. Non tutta la storia — per quella c'è P12.
            'changes' => PendingChange::visibleTo($request->user())
                ->with('character')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * La scheda di un personaggio, senza dire quale sezione.
     *
     * Si apre sulla **prima che questo lettore può vedere**, e non sempre sulla
     * stessa: chi la possiede entra dal Turno, chi passa entra dalla Storia,
     * che è l'unica che gli resta (P14). Passare qui `DEFAULT` a occhi chiusi
     * darebbe un 404 sull'indirizzo principale del personaggio, cioè su quello
     * che sta scritto in ogni collegamento della Gilda.
     *
     * Tutto quello che si vede è calcolato: classe armatura, iniziativa,
     * bonus, CD e slot non sono salvati da nessuna parte.
     */
    public function show(Character $character): View
    {
        return $this->sheet($character, null);
    }

    /**
     * Le altre sezioni. La sezione arriva dall'indirizzo ed è già ristretta ai
     * valori dell'enum dalla rotta, quindi qui `from()` non può fallire.
     */
    public function section(Character $character, string $sezione): View
    {
        return $this->sheet($character, SheetSection::from($sezione));
    }

    /**
     * Una sezione della scheda.
     *
     * Il caricamento anticipato non è un'ottimizzazione ma un requisito:
     * `preventLazyLoading` è attivo fuori produzione, e una relazione
     * dimenticata fa fallire la pagina invece di generare in silenzio una query
     * per riga (§8.6 del brief). Quali servano lo dice la sezione, che è
     * l'unica a saperlo.
     *
     * Una sezione che non c'entra con questo personaggio — Magia per un
     * barbaro — non è nascosta: **non esiste**, e chiederla è un 404. La
     * linguetta non c'è, e un indirizzo scritto a mano non deve trovare una
     * pagina vuota.
     *
     * **La scheda di un altro è la stessa pagina, ridotta a una sezione** (P14).
     * Vale lo stesso meccanismo: quattro sezioni su cinque non sono nascoste con
     * un `@if`, per chi passa non esistono — la linguetta non c'è e l'indirizzo
     * scritto a mano è un 404. Così quei dati non finiscono nella pagina, e
     * nemmeno nella query che la riempie.
     *
     * @param  SheetSection|null  $sezione  quale aprire; `null` vuol dire «la
     *                                      prima che questo lettore può vedere»
     */
    private function sheet(Character $character, ?SheetSection $sezione): View
    {
        $completa = request()->user()?->can('viewFullSheet', $character) ?? false;
        $sezioni = SheetSection::forCharacter($character, tutte: $completa);

        // La prima è quella che si apre entrando: il Turno per chi la possiede,
        // la Storia per chi passa. La Storia non si filtra mai — né per il
        // personaggio né per il lettore — quindi ce n'è sempre almeno una.
        $sezione ??= $sezioni[0];

        abort_unless(in_array($sezione, $sezioni, true), 404);

        $character->load($this->ordered($sezione->relations(), $completa));

        return view('characters.show', [
            'character' => $character,
            'sezione' => $sezione,
            'sezioni' => $sezioni,
            'completa' => $completa,
            'effective' => $character->effectiveScores(),
            'base' => $character->baseScores(),
            'slots' => $character->spellSlots(),
        ]);
    }

    /**
     * L'ordine di lettura di certe relazioni non è un dettaglio: lo zaino si
     * legge per categoria, i talenti per livello, gli incantesimi per livello.
     *
     * **E qui si taglia lo zaino di un altro** (P14). A chi passa gli oggetti
     * arrivano già filtrati alla sola vetrina: non è la vista a nasconderli, è
     * la query a non chiederli. È la stessa idea delle sezioni private — quello
     * che non si deve vedere non viene caricato — applicata dentro una sezione
     * che invece resta, perché la vetrina il proprietario ce l'ha messa apposta.
     *
     * Il che vuol dire che con `$completa` falso `$character->items` **non è**
     * l'inventario: è la vetrina. Non è un problema perché tutto quello che
     * legge davvero l'inventario — `equipped()`, la classe armatura, gli
     * attacchi — sta in pezzi di pagina che a chi passa non compaiono. Ma va
     * saputo prima di andarci a leggere qualcosa di nuovo.
     *
     * @param  list<string>  $relazioni
     * @param  bool  $completa  se il lettore ha diritto alla scheda intera
     * @return array<string,callable>|list<string>
     */
    private function ordered(array $relazioni, bool $completa): array
    {
        $ordini = [
            'items' => fn ($query) => ($completa ? $query : $query->tradeable())
                ->orderBy('category')->orderBy('name'),
            'feats' => fn ($query) => $query->orderBy('level'),
            'spells' => fn ($query) => $query->orderBy('level')->orderBy('name'),
        ];

        return collect($relazioni)
            ->mapWithKeys(fn (string $nome) => isset($ordini[$nome])
                ? [$nome => $ordini[$nome]]
                : [$nome => fn ($query) => $query])
            ->all();
    }

    /**
     * Il registro del personaggio (P11): l'estratto conto.
     *
     * Le righe si scrivono e non si toccano più. Un movimento annullato non
     * sparisce — resta dov'è, segnato, e l'annullamento è una riga in più più
     * avanti: è la differenza fra un registro e una lavagna.
     *
     * Qui dentro c'è anche lo storico del venduto (era P25): una vendita è un
     * movimento di oro come gli altri, e tenerla in una pagina sua vorrebbe
     * dire due estratti conto da confrontare per ricostruire una settimana.
     */
    public function ledger(Request $request, Character $character): View
    {
        $this->authorize('viewLedger', $character);

        /*
         * Chi conduce vede il registro di **tutti** (M20), non solo di questo
         * personaggio: qui si cerca dove è finito qualcosa, e quel qualcosa può
         * essere passato su un'altra scheda. Il giocatore vede il proprio, e
         * basta — per lui la pagina resta quella di sempre, senza filtri.
         *
         * I filtri stanno nell'indirizzo, come nel Libro Mastro: una pagina
         * filtrata si può anche mandare a qualcuno. Il personaggio non è un
         * filtro nell'URL ma il segmento della rotta — si sceglie aprendo il suo
         * registro — e «tutti» è l'unica cosa che lo allarga, ancorata a dove si
         * era.
         */
        if (! $request->user()->isDm() && ! $request->user()->isAdmin()) {
            return view('characters.ledger', [
                'character' => $character,
                'filtrabile' => false,
                'entries' => $character->ledgerEntries()
                    ->with('actor')
                    ->latestFirst()
                    ->get(),
            ]);
        }

        $tutti = $request->string('pg')->toString() === 'tutti';
        $tipo = LedgerAction::tryFrom($request->string('tipo')->toString());
        $giorni = in_array($request->integer('periodo'), [7, 30, 90], true)
            ? $request->integer('periodo')
            : 0;

        $entries = LedgerEntry::query()
            ->with(['actor', 'character'])
            ->when(! $tutti, fn ($query) => $query->where('character_id', $character->getKey()))
            ->when($tipo !== null, fn ($query) => $query->where('action', $tipo))
            ->when($giorni > 0, fn ($query) => $query->where('created_at', '>=', now()->subDays($giorni)))
            ->latestFirst()
            ->get();

        return view('characters.ledger', [
            'character' => $character,
            'filtrabile' => true,
            'entries' => $entries,
            'tutti' => $tutti,
            'tipo' => $tipo,
            'giorni' => $giorni,
            'personaggi' => Character::orderBy('name')->get(['id', 'name']),
            'azioni' => LedgerAction::cases(),
        ]);
    }
}
