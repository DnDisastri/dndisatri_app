<?php

namespace App\Livewire;

use App\Actions\Characters\AdjustHitPoints;
use App\Enums\Condition;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Monster;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Il tracker di combattimento: l'iniziativa, i punti ferita, le condizioni, il
 * turno e il round — tutto quello che il DM tiene sotto mano mentre si combatte.
 *
 * Il principio è uno solo, e decide tutto: **cosa è reale**.
 *
 * - Una riga **eroe** non ha PF suoi: legge quelli veri della scheda, e il
 *   danno che gli fai qui li scrive **davvero** (`AdjustHitPoints`, lo stesso
 *   della scheda) — il giocatore li vede calare sul suo telefono.
 * - Una riga **mostro** è effimera: PF e CA vivono con lo scontro, nel json
 *   della serata (`initiative`), e non toccano niente di condiviso.
 *
 * L'ordine è per iniziativa, dal più alto. Il turno si segue con un **id
 * stabile**, non con un indice: così riordinando la fila il puntatore non
 * scivola su un altro combattente.
 */
class CombatTracker extends Component
{
    #[Locked]
    public int $sessionId;

    public int $round = 1;

    /** Di chi è il turno: l'id del combattente, non un indice. */
    public ?string $turnoId = null;

    /** @var list<array<string, mixed>> */
    public array $combattenti = [];

    /** Il colpo che si sta per infliggere/curare, per riga (id stabile). Non si salva. */
    public array $colpo = [];

    // Aggiungi mostro.
    public bool $mostraAggiungiMostro = false;

    // Al volo.
    public string $mostroNome = '';

    public ?int $mostroHp = null;

    public ?int $mostroAc = null;

    /** Salva anche nel bestiario, così la prossima volta si pesca. */
    public bool $salvaNelBestiario = false;

    // Dal bestiario.
    public string $cercaMostro = '';

    /** Quale riga ha aperto il pannello delle condizioni. */
    public ?string $condizioniAperte = null;

    /** Quale mostro ha lo statblock esteso aperto nel modale. */
    public ?string $statblockAperto = null;

    public function mount(GameSession $session): void
    {
        $this->assicuraDm();

        $this->sessionId = $session->getKey();

        $dati = $session->initiative ?? [];
        $this->round = (int) ($dati['round'] ?? 1);
        $this->turnoId = $dati['turnoId'] ?? null;
        // `ordine` è la vecchia forma (solo iniziativa): si legge lo stesso.
        $this->combattenti = $this->normalizza($dati['combattenti'] ?? $dati['ordine'] ?? []);
    }

    private function assicuraDm(): void
    {
        abort_unless(auth()->user()?->isDm() ?? false, 403);
    }

    private function sessione(): GameSession
    {
        return GameSession::findOrFail($this->sessionId);
    }

    /**
     * Riempie i buchi, traduce la vecchia forma e **tiene solo campi e valori
     * ammessi**: `combattenti` è una proprietà pubblica, quindi arriva dal
     * client, e va ripulita — non solo in lettura (al `mount`), ma anche prima
     * di salvare (vedi `persiste`). L'output è comunque sfuggito, ma un dato
     * pulito è un dato in meno di cui fidarsi.
     */
    private function normalizza(array $righe): array
    {
        return array_values(array_map(fn (array $c) => [
            'id' => $c['id'] ?? (string) Str::uuid(),
            'tipo' => in_array($c['tipo'] ?? null, ['pg', 'mostro'], true)
                ? $c['tipo']
                : (($c['pg'] ?? false) ? 'pg' : 'mostro'),
            'nome' => mb_substr((string) ($c['nome'] ?? '—'), 0, 80),
            'iniziativa' => (int) ($c['iniziativa'] ?? 0),
            'characterId' => isset($c['characterId']) ? (int) $c['characterId'] : null,
            'hp' => isset($c['hp']) ? (int) $c['hp'] : null,
            'hpMax' => isset($c['hpMax']) ? (int) $c['hpMax'] : null,
            'ac' => isset($c['ac']) ? (int) $c['ac'] : null,
            // Lo statblock del mostro, quando viene dal bestiario: viaggia con
            // la serata, così il modale esteso funziona anche senza ripescarlo.
            'speed' => isset($c['speed']) ? mb_substr((string) $c['speed'], 0, 40) : null,
            'attacks' => array_values($c['attacks'] ?? []),
            'traits' => isset($c['traits']) ? (string) $c['traits'] : null,
            'monsterId' => isset($c['monsterId']) ? (int) $c['monsterId'] : null,
            // Solo condizioni vere del manuale: quello che il client inventa cade.
            'condizioni' => array_values(array_filter(
                $c['condizioni'] ?? [],
                fn ($v) => Condition::tryFrom((string) $v) !== null,
            )),
        ], $righe));
    }

    // === Comporre la fila ===

    /**
     * Mette in fila gli eroi del tavolo, a iniziativa zero: al DM resta da
     * scrivere solo i tiri. Chi c'è già non entra due volte.
     */
    public function popolaDalTavolo(): void
    {
        $this->assicuraDm();

        $giàDentro = collect($this->combattenti)->pluck('characterId')->filter()->all();

        foreach ($this->sessione()->campaign->roster() as $pg) {
            if (in_array($pg->id, $giàDentro, true)) {
                continue;
            }

            $this->combattenti[] = [
                'id' => (string) Str::uuid(),
                'tipo' => 'pg',
                'nome' => $pg->name,
                'iniziativa' => 0,
                'characterId' => $pg->id,
                'hp' => null,
                'hpMax' => null,
                'ac' => null,
                'condizioni' => [],
            ];
        }

        $this->riordinaEpersiste();
    }

    public function aggiungiMostro(): void
    {
        $this->assicuraDm();
        $this->validate([
            'mostroNome' => ['required', 'string', 'max:60'],
            'mostroHp' => ['required', 'integer', 'min:1'],
            'mostroAc' => ['required', 'integer', 'between:1,40'],
        ], [
            'mostroNome.required' => 'Serve un nome.',
            'mostroHp.required' => 'Servono i PF.',
            'mostroAc.required' => 'Serve la CA.',
        ]);

        // Se lo si vuole tenere, entra anche nel bestiario: la prossima volta
        // si pesca invece di riscriverlo.
        if ($this->salvaNelBestiario) {
            Monster::create([
                'name' => $this->mostroNome,
                'hp' => $this->mostroHp,
                'ac' => $this->mostroAc,
                'created_by' => auth()->id(),
            ]);
        }

        $this->combattenti[] = [
            'id' => (string) Str::uuid(),
            'tipo' => 'mostro',
            'nome' => $this->mostroNome,
            'iniziativa' => 0,
            'characterId' => null,
            'hp' => $this->mostroHp,
            'hpMax' => $this->mostroHp,
            'ac' => $this->mostroAc,
            'speed' => null,
            'attacks' => [],
            'traits' => null,
            'monsterId' => null,
            'condizioni' => [],
        ];

        $this->reset('mostroNome', 'mostroHp', 'mostroAc', 'salvaNelBestiario', 'mostraAggiungiMostro');
        $this->riordinaEpersiste();
    }

    /** Pesca un mostro dal bestiario: lo copia nella serata, PF a pieno. */
    public function aggiungiDalBestiario(int $monsterId): void
    {
        $this->assicuraDm();

        $monster = Monster::find($monsterId);

        if ($monster === null) {
            return;
        }

        $this->combattenti[] = array_merge([
            'id' => (string) Str::uuid(),
            'tipo' => 'mostro',
            'iniziativa' => 0,
            'characterId' => null,
            'condizioni' => [],
        ], $monster->toCombatant());

        $this->reset('cercaMostro', 'mostraAggiungiMostro');
        $this->riordinaEpersiste();
    }

    // === Statblock esteso ===

    public function apriStatblock(string $id): void
    {
        $this->statblockAperto = $id;
    }

    public function chiudiStatblock(): void
    {
        $this->statblockAperto = null;
    }

    public function rimuovi(string $id): void
    {
        $this->assicuraDm();

        $this->combattenti = array_values(array_filter(
            $this->combattenti, fn (array $c) => $c['id'] !== $id,
        ));

        if ($this->turnoId === $id) {
            $this->turnoId = null;
        }

        $this->persiste();
    }

    public function azzera(): void
    {
        $this->assicuraDm();

        $this->combattenti = [];
        $this->turnoId = null;
        $this->round = 1;
        $this->persiste();
    }

    // === Turno e round ===

    /** Passa al prossimo; dopo l'ultimo si ricomincia dal primo e sale il round. */
    public function prossimo(): void
    {
        $this->assicuraDm();

        if ($this->combattenti === []) {
            return;
        }

        $ids = array_column($this->combattenti, 'id');

        if ($this->turnoId === null) {
            $this->turnoId = $ids[0];
            $this->persiste();

            return;
        }

        $i = array_search($this->turnoId, $ids, true);
        $prossimo = $i === false ? 0 : $i + 1;

        if ($prossimo >= count($ids)) {
            $prossimo = 0;
            $this->round++;
        }

        $this->turnoId = $ids[$prossimo];
        $this->persiste();
    }

    // === Punti ferita ===

    public function danno(string $id): void
    {
        $this->muoviPf($id, 'danno');
    }

    public function cura(string $id): void
    {
        $this->muoviPf($id, 'cura');
    }

    /**
     * L'eroe passa da `AdjustHitPoints`, che scrive sui PF veri (riflesso sulla
     * scheda); il mostro muove il numero effimero salvato qui.
     */
    private function muoviPf(string $id, string $verso): void
    {
        $this->assicuraDm();

        $quanti = (int) ($this->colpo[$id] ?? 0);
        $indice = $this->indiceDi($id);

        if ($quanti < 1 || $indice === null) {
            return;
        }

        $c = $this->combattenti[$indice];

        if ($c['tipo'] === 'pg' && $c['characterId'] !== null) {
            $character = Character::with(['items', 'itemEffects'])->find($c['characterId']);

            if ($character !== null) {
                $this->authorize('manageHitPoints', $character);

                $azione = app(AdjustHitPoints::class);
                $verso === 'danno'
                    ? $azione->damage($character, $quanti)
                    : $azione->heal($character, $quanti);
            }
        } else {
            $hp = (int) ($c['hp'] ?? 0);
            $max = (int) ($c['hpMax'] ?? $hp);

            $this->combattenti[$indice]['hp'] = $verso === 'danno'
                ? max(0, $hp - $quanti)
                : min($max, $hp + $quanti);

            $this->persiste();
        }

        $this->colpo[$id] = null;
    }

    /**
     * Segna un tiro contro morte di un eroe a terra (tappa B): scrive sullo
     * stesso dato che il giocatore vede sulla sua scheda.
     */
    public function tiroMorte(string $id, string $tipo, int $n): void
    {
        $this->assicuraDm();

        $indice = $this->indiceDi($id);

        if ($indice === null || $this->combattenti[$indice]['tipo'] !== 'pg') {
            return;
        }

        $character = Character::find($this->combattenti[$indice]['characterId']);

        if ($character !== null) {
            $this->authorize('manageHitPoints', $character);
            $character->segnaTiroMorte($tipo, $n);
        }
    }

    // === Condizioni ===

    public function apriCondizioni(string $id): void
    {
        $this->condizioniAperte = $this->condizioniAperte === $id ? null : $id;
    }

    /** Mette la condizione se non c'è, la toglie se c'è. */
    public function condizione(string $id, string $valore): void
    {
        $this->assicuraDm();

        if (Condition::tryFrom($valore) === null) {
            return;
        }

        $indice = $this->indiceDi($id);

        if ($indice === null) {
            return;
        }

        $attuali = $this->combattenti[$indice]['condizioni'];

        $this->combattenti[$indice]['condizioni'] = in_array($valore, $attuali, true)
            ? array_values(array_filter($attuali, fn ($v) => $v !== $valore))
            : [...$attuali, $valore];

        $this->persiste();
    }

    // === Iniziativa (riordino) ===

    /** Cambiato un numero in riga: si rimette in ordine all'uscita dal campo. */
    public function updated(string $name): void
    {
        if (preg_match('/^combattenti\.\d+\.iniziativa$/', $name)) {
            $this->riordinaEpersiste();
        }
    }

    private function riordinaEpersiste(): void
    {
        foreach ($this->combattenti as $k => $c) {
            $this->combattenti[$k]['iniziativa'] = (int) $c['iniziativa'];
        }

        usort($this->combattenti, fn (array $a, array $b) => $b['iniziativa'] <=> $a['iniziativa']);
        $this->persiste();
    }

    private function indiceDi(string $id): ?int
    {
        foreach ($this->combattenti as $i => $c) {
            if ($c['id'] === $id) {
                return $i;
            }
        }

        return null;
    }

    private function persiste(): void
    {
        // Si ripulisce prima di scrivere: `combattenti` arriva dal client, e
        // quello che si salva dev'essere della forma giusta (R4).
        $this->combattenti = $this->normalizza($this->combattenti);

        $this->sessione()->forceFill([
            'initiative' => [
                'round' => $this->round,
                'turnoId' => $this->turnoId,
                'combattenti' => $this->combattenti,
            ],
        ])->save();
    }

    public function render()
    {
        // I personaggi degli eroi, in blocco: servono per i PF veri e la CA.
        $ids = collect($this->combattenti)
            ->where('tipo', 'pg')->pluck('characterId')->filter()->all();

        $personaggi = $ids === []
            ? collect()
            : Character::with(['items', 'itemEffects'])->whereIn('id', $ids)->get()->keyBy('id');

        // Il bestiario da pescare: cerca solo col pannello aperto e qualcosa scritto.
        $mostriTrovati = ($this->mostraAggiungiMostro && trim($this->cercaMostro) !== '')
            ? Monster::search(trim($this->cercaMostro))->orderBy('name')->limit(8)->get()
            : collect();

        return view('livewire.combat-tracker', [
            'personaggi' => $personaggi,
            'condizioniDisponibili' => Condition::elenco(),
            'mostriTrovati' => $mostriTrovati,
            'statblock' => $this->statblockAperto !== null
                ? collect($this->combattenti)->firstWhere('id', $this->statblockAperto)
                : null,
        ]);
    }
}
