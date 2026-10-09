<?php

namespace App\Livewire;

use App\Actions\Characters\AdjustHitPoints;
use App\Enums\Condition;
use App\Enums\EncounterStatus;
use App\Models\Character;
use App\Models\Encounter;
use App\Models\Monster;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Il tracker di combattimento: iniziativa, PF, condizioni, turno e round.
 *
 * - Eroe: legge e scrive i PF veri della scheda (`AdjustHitPoints`).
 * - Mostro e ospite: PF e CA vivono solo nel json `combatants`.
 * - Ospite: ricorda il suo posto (`bookingId`); quando quel posto ha un personaggio
 *   (ospite collegato all'account), la riga diventa la sua scheda.
 *
 * Il turno segue un id stabile, non un indice: riordinando la fila non scivola.
 */
class CombatTracker extends Component
{
    #[Locked]
    public int $encounterId;

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

    public function mount(Encounter $encounter): void
    {
        $this->assicuraDm();

        $this->encounterId = $encounter->getKey();
        $this->round = (int) $encounter->round;
        $this->turnoId = $encounter->turn_id;
        $this->combattenti = $this->normalizza($encounter->combatants ?? []);

        // Senza passare da persiste(): aprire la pagina non deve mettere in corso lo scontro.
        if ($this->sostituisciOspiti()) {
            $encounter->forceFill(['combatants' => $this->combattenti])->save();
        }
    }

    /**
     * Gli ospiti il cui posto ha ora un personaggio diventano quel personaggio: restano
     * iniziativa, condizioni e turno, mentre PF e CA tornano a essere quelli della scheda.
     */
    private function sostituisciOspiti(): bool
    {
        $sessione = $this->scontro()->session;
        $ids = collect($this->combattenti)->where('tipo', 'ospite')->pluck('bookingId')->filter();

        if ($sessione === null || $ids->isEmpty()) {
            return false;
        }

        // Il posto si cerca fra quelli della sessione: un id arrivato dal client non basta.
        $posti = $sessione->bookings()->whereIn('id', $ids)->whereNotNull('character_id')->with('character')->get()->keyBy('id');
        $giàPg = collect($this->combattenti)->where('tipo', 'pg')->pluck('characterId')->all();
        $cambiato = false;

        foreach ($this->combattenti as $i => $c) {
            $pg = $c['tipo'] === 'ospite' ? $posti->get($c['bookingId'])?->character : null;

            if ($pg === null || ! $pg->isAlive()) {
                continue;
            }

            $cambiato = true;

            // Il suo eroe è già in fila: la riga dell'ospite è un doppione.
            if (in_array($pg->id, $giàPg, true)) {
                unset($this->combattenti[$i]);

                continue;
            }

            $this->combattenti[$i] = array_merge($c, [
                'tipo' => 'pg',
                'nome' => $pg->name,
                'characterId' => $pg->id,
                'bookingId' => null,
                'hp' => null,
                'hpMax' => null,
                'ac' => null,
            ]);
            $giàPg[] = $pg->id;
        }

        $this->combattenti = array_values($this->combattenti);

        return $cambiato;
    }

    private function assicuraDm(): void
    {
        abort_unless(auth()->user()?->isDm() ?? false, 403);
    }

    private function scontro(): Encounter
    {
        return Encounter::findOrFail($this->encounterId);
    }

    /** Si può riaprire: a volte il combattimento riprende dopo una pausa. */
    public function concludi(): void
    {
        $this->assicuraDm();

        $this->scontro()->forceFill(['status' => EncounterStatus::Ended, 'ended_at' => now()])->save();
    }

    public function riapri(): void
    {
        $this->assicuraDm();

        $this->scontro()->forceFill(['status' => EncounterStatus::Running, 'ended_at' => null])->save();
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
            'tipo' => in_array($c['tipo'] ?? null, ['pg', 'mostro', 'ospite'], true)
                ? $c['tipo']
                : (($c['pg'] ?? false) ? 'pg' : 'mostro'),
            'nome' => mb_substr((string) ($c['nome'] ?? 'Vuoto'), 0, 80),
            'iniziativa' => (int) ($c['iniziativa'] ?? 0),
            'characterId' => isset($c['characterId']) ? (int) $c['characterId'] : null,
            'bookingId' => isset($c['bookingId']) ? (int) $c['bookingId'] : null,
            'hp' => isset($c['hp']) ? (int) $c['hp'] : null,
            'hpMax' => isset($c['hpMax']) ? (int) $c['hpMax'] : null,
            'ac' => isset($c['ac']) ? (int) $c['ac'] : null,
            // Lo statblock del mostro, quando viene dal bestiario: viaggia con
            // la sessione, così il modale esteso funziona anche senza ripescarlo.
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
     * Mette in fila gli eroi a iniziativa zero: i confermati della sessione collegata,
     * altrimenti i presenti, altrimenti chi ha giocato la campagna. Poi chi ha un posto
     * confermato ma nessuna scheda (gli ospiti), come ospite.
     */
    public function aggiungiEroi(): void
    {
        $this->assicuraDm();
        $this->sostituisciOspiti();

        $giàDentro = collect($this->combattenti)->pluck('characterId')->filter()->all();

        $scontro = $this->scontro();
        $sessione = $scontro->session;
        $eroi = $sessione?->bookedCharacters() ?? collect();

        if ($eroi->isEmpty() && $sessione !== null) {
            $eroi = $sessione->playedCharacters()->alive()->orderBy('name')->get();
        }

        if ($eroi->isEmpty()) {
            $eroi = $scontro->campaign->roster();
        }

        foreach ($eroi as $pg) {
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

        // Senza scheda (ospiti, o collegati che non hanno ancora scelto l'eroe): PF e CA li scrive il DM.
        $giàOspiti = collect($this->combattenti)->where('tipo', 'ospite');

        foreach ($sessione?->bookings()->confirmed()->whereNull('character_id')->with('user')->get() ?? [] as $ospite) {
            $nome = $ospite->guest_character ?: $ospite->displayName();

            if ($giàOspiti->contains('bookingId', $ospite->id) || $giàOspiti->contains('nome', $nome)) {
                continue;
            }

            $this->combattenti[] = [
                'id' => (string) Str::uuid(),
                'tipo' => 'ospite',
                'nome' => $nome,
                'iniziativa' => 0,
                'characterId' => null,
                'bookingId' => $ospite->id,
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

    /** Pesca un mostro dal bestiario: lo copia nel combattimento, PF a pieno. */
    public function aggiungiDalBestiario(int $monsterId): void
    {
        $this->assicuraDm();

        // Pubblico o della campagna di questo combattimento: gli altri non si pescano.
        $monster = Monster::usableInCampaign($this->scontro()->campaign_id)
            ->whereKey($monsterId)
            ->first();

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

        // PF massimi e CA dell'ospite, scritti a mano: i PF attuali partono pieni.
        if (preg_match('/^combattenti\.(\d+)\.(hpMax|ac)$/', $name, $m) && ($this->combattenti[$m[1]]['tipo'] ?? null) === 'ospite') {
            $this->assicuraDm();
            $c = &$this->combattenti[$m[1]];
            $c['hpMax'] = max(0, min(999, (int) $c['hpMax']));
            $c['ac'] = $c['ac'] === null || $c['ac'] === '' ? null : max(0, min(40, (int) $c['ac']));
            $c['hp'] = $c['hp'] === null ? $c['hpMax'] : min((int) $c['hp'], $c['hpMax']);
            unset($c);
            $this->persiste();
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

        $scontro = $this->scontro();

        $scontro->forceFill([
            'round' => $this->round,
            'turn_id' => $this->turnoId,
            'combatants' => $this->combattenti,
            // Il primo turno lo mette in corso: preparare non è ancora combattere.
            'status' => $scontro->status === EncounterStatus::Prepared && $this->turnoId !== null
                ? EncounterStatus::Running
                : $scontro->status,
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
            ? Monster::search(trim($this->cercaMostro))
                ->usableInCampaign($this->scontro()->campaign_id)
                ->orderBy('name')->limit(8)->get()
            : collect();

        return view('livewire.combat-tracker', [
            'scontro' => $this->scontro(),
            'personaggi' => $personaggi,
            'condizioniDisponibili' => Condition::elenco(),
            'mostriTrovati' => $mostriTrovati,
            'statblock' => $this->statblockAperto !== null
                ? collect($this->combattenti)->firstWhere('id', $this->statblockAperto)
                : null,
        ]);
    }
}
