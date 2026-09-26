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
    /** I miei eroi: da qui si va alla scheda, alle proposte, al registro. Sempre una collezione (un DM può averne più d'uno). */
    public function index(Request $request): View
    {
        $characters = $request->user()->characters()
            ->with('classes')
            ->orderByDesc('died_at')
            ->orderBy('name')
            ->get();

        return view('characters.index', [
            'characters' => $characters,

            // Le richieste da decidere e le ultime decise, non tutta la storia.
            'changes' => PendingChange::visibleTo($request->user())
                ->with('character')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * La scheda senza sezione: apre sulla prima che il lettore può vedere
     * (proprietario dal Turno, chi passa dalla Storia). Non `DEFAULT` fisso,
     * che a un estraneo darebbe 404 sull'indirizzo principale.
     */
    public function show(Character $character): View
    {
        return $this->sheet($character, null);
    }

    /** Le altre sezioni: la rotta limita già ai valori dell'enum, quindi `from()` non fallisce. */
    public function section(Character $character, string $sezione): View
    {
        return $this->sheet($character, SheetSection::from($sezione));
    }

    /**
     * Prepara la scheda.
     *
     * Il caricamento anticipato è un requisito, non un'ottimizzazione:
     * `preventLazyLoading` fa fallire la pagina se manca una relazione, invece
     * di generare una query per riga. Quali servano lo dice la sezione.
     *
     * Una sezione che non c'entra col personaggio (Magia per un barbaro) è un
     * 404, non una pagina nascosta. La scheda di un altro è la stessa pagina
     * ridotta alle sezioni pubbliche: le private non si disegnano e non si
     * caricano.
     *
     * @param  SheetSection|null  $sezione  quale aprire; null = la prima visibile al lettore
     */
    private function sheet(Character $character, ?SheetSection $sezione): View
    {
        $completa = request()->user()?->can('viewFullSheet', $character) ?? false;
        $sezioni = SheetSection::forCharacter($character, tutte: $completa);

        // La prima visibile: Turno per il proprietario, Storia per chi passa
        // (la Storia c'è sempre).
        $sezione ??= $sezioni[0];

        abort_unless(in_array($sezione, $sezioni, true), 404);

        // Si disegnano tutte le sezioni (swipe senza reload): serve l'unione
        // delle relazioni. Le private restano fuori da `$sezioni`, e dalla pagina.
        $relazioni = collect($sezioni)
            ->flatMap(fn (SheetSection $s) => $s->relations())
            ->unique()
            ->values()
            ->all();

        $character->load($this->ordered($relazioni, $completa));

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
     * L'ordine di certe relazioni: zaino per categoria, talenti e incantesimi
     * per livello.
     *
     * Con `$completa` falso gli oggetti arrivano filtrati alla sola vetrina (lo
     * taglia la query, non la vista): lì `$character->items` è la vetrina, non
     * l'inventario.
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
