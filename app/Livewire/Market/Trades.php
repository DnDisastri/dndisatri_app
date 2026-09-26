<?php

namespace App\Livewire\Market;

use App\Actions\Market\AcceptTradeRequest;
use App\Actions\Market\CreateTradeRequest;
use App\Actions\Market\ResolveTrade;
use App\Actions\Market\ResolveTradeRequest;
use App\Actions\Supervision\Supervisor;
use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\Character;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\TradeRequest;
use Livewire\Component;

/**
 * Gli scambi diretti fra giocatori.
 *
 * Proporre e accettare passano dal **Supervisor** (vedi la nota in `Listings`).
 * Rifiutare e ritirare no: chiudere una proposta senza eseguirla non muove
 * niente, e non c'è niente da vigilare.
 */
class Trades extends Component
{
    use ActsAsCharacter;

    public ?int $toCharacterId = null;

    /** @var list<string> nomi degli oggetti offerti */
    public array $give = [];

    /** @var list<string> nomi degli oggetti chiesti */
    public array $want = [];

    public int $giveGp = 0;

    public int $wantGp = 0;

    public string $message = '';

    /**
     * Quello che si chiede **a parole**, quando non è in vetrina.
     *
     * Non è un oggetto scelto da un elenco: è una diceria, e quindi una
     * stringa. Se questo campo è pieno non parte uno scambio ma una richiesta.
     */
    public string $chiedo = '';

    /** La richiesta ricevuta a cui si sta rispondendo, nel riquadro. */
    public ?int $richiestaAperta = null;

    /** @var list<string> quello che si dà rispondendo a una richiesta */
    public array $offro = [];

    public int $offroGp = 0;

    public function mount(): void
    {
        $this->resolveCharacter();
        $this->preselezionaDestinatario();
    }

    /**
     * Arrivando dalla vetrina di un altro (P14), il destinatario è già scelto.
     *
     * La scheda passa `?a={id}`, così si atterra sul modulo con «A chi» già
     * riempito e la sua vetrina già mostrata, invece di dover ricominciare da
     * capo scegliendo la persona che si stava già guardando.
     *
     * L'id arriva dal browser e non ci si fida: si prende solo se è un
     * personaggio **vivo** e **non è quello** con cui si sta usando il mercato.
     * Uno inventato o già morto lascia il modulo com'era, vuoto.
     */
    private function preselezionaDestinatario(): void
    {
        $a = request()->integer('a');

        if ($a <= 0) {
            return;
        }

        $valido = Character::alive()->whereKey($a)->whereKeyNot($this->characterId)->exists();

        if ($valido) {
            $this->toCharacterId = $a;
        }
    }

    /** Cambiando destinatario, quello che gli si chiedeva non ha più senso. */
    public function updatedToCharacterId(): void
    {
        $this->want = [];
        $this->chiedo = '';
    }

    public function propose(): void
    {
        $character = $this->requireCharacter();

        $to = Character::alive()->find($this->toCharacterId);

        if ($to === null) {
            $this->addError('scambio', 'Scegli a chi proporre lo scambio.');

            return;
        }

        /*
         * Due strade e un modulo solo. Quello che si spunta dalla vetrina è un
         * oggetto che esiste, e allora è una proposta; quello che si scrive a
         * parole non lo è, e allora è una richiesta. Insieme non hanno senso —
         * sarebbe mezza proposta eseguibile e mezza no — e invece di scegliere
         * al posto di chi scrive, glielo si dice.
         */
        if ($this->chiedo !== '' && $this->want !== []) {
            $this->addError('scambio', 'Scegli: o spunti qualcosa dalla sua vetrina, o chiedi a parole.');

            return;
        }

        if ($this->chiedo !== '') {
            $this->request($character, $to);

            return;
        }

        try {
            $result = app(Supervisor::class)->proposeTrade(
                actor: auth()->user(),
                from: $character,
                to: $to,
                give: $this->asItems($this->give),
                want: $this->asItems($this->want),
                giveGp: $this->giveGp,
                wantGp: $this->wantGp,
                message: $this->message ?: null,
            );

            $this->reset('give', 'want', 'giveGp', 'wantGp', 'message');

            session()->flash('mercato', $result instanceof SupervisedAction
                ? 'Sei sotto richiamo: la proposta è in attesa che un DM la approvi.'
                : 'Proposta inviata.');
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    /** La domanda a parole: non muove niente, e non passa dalla vigilanza. */
    private function request(Character $character, Character $to): void
    {
        try {
            app(CreateTradeRequest::class)->handle(
                from: $character,
                to: $to,
                wanted: $this->chiedo,
                offered: array_values(array_filter($this->give)),
                offeredGp: $this->giveGp,
                message: $this->message ?: null,
            );

            $this->reset('give', 'want', 'giveGp', 'wantGp', 'message', 'chiedo');

            session()->flash('mercato', 'Richiesta inviata: adesso tocca a lui.');
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    // === Le richieste ricevute ===

    /** Apre il riquadro per rispondere: si sceglie dal proprio zaino. */
    public function apriRichiesta(int $requestId): void
    {
        $request = TradeRequest::findOrFail($requestId);
        $this->authorize('accept', $request);

        $this->richiestaAperta = $request->getKey();
        $this->offro = [];
        $this->offroGp = 0;
        $this->resetErrorBag('scambio');
    }

    public function chiudiRichiesta(): void
    {
        $this->richiestaAperta = null;
    }

    /**
     * «Sì, ce l'ho»: dalla richiesta nasce una proposta di scambio, che
     * l'altro dovrà confermare.
     */
    public function accettaRichiesta(): void
    {
        $request = TradeRequest::findOrFail($this->richiestaAperta);
        $this->authorize('accept', $request);

        try {
            $result = app(AcceptTradeRequest::class)->handle(
                $request,
                auth()->user(),
                $this->asItems($this->offro),
                $this->offroGp,
            );

            $this->chiudiRichiesta();

            session()->flash('mercato', $result instanceof SupervisedAction
                ? 'Sei sotto richiamo: la proposta è in attesa che un DM la approvi.'
                : 'Proposta mandata: ora tocca a lui confermare.');
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    public function rifiutaRichiesta(int $requestId): void
    {
        $this->closeRequest($requestId, TradeStatus::Rejected, 'reject', 'Richiesta rifiutata.');
    }

    public function ritiraRichiesta(int $requestId): void
    {
        $this->closeRequest($requestId, TradeStatus::Cancelled, 'cancel', 'Richiesta ritirata.');
    }

    private function closeRequest(int $requestId, TradeStatus $status, string $ability, string $done): void
    {
        $request = TradeRequest::findOrFail($requestId);
        $this->authorize($ability, $request);

        try {
            app(ResolveTradeRequest::class)->handle($request, $status);

            $this->chiudiRichiesta();
            session()->flash('mercato', $done);
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    public function accept(int $tradeId): void
    {
        $trade = Trade::findOrFail($tradeId);
        $this->authorize('accept', $trade);

        try {
            $result = app(Supervisor::class)->acceptTrade(auth()->user(), $trade);

            session()->flash('mercato', $result instanceof SupervisedAction
                ? 'Sei sotto richiamo: l\'accettazione è in attesa che un DM la approvi.'
                : 'Scambio concluso.');
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    public function reject(int $tradeId): void
    {
        $this->close($tradeId, TradeStatus::Rejected, 'reject', 'Proposta rifiutata.');
    }

    public function withdraw(int $tradeId): void
    {
        $this->close($tradeId, TradeStatus::Cancelled, 'cancel', 'Proposta ritirata.');
    }

    private function close(int $tradeId, TradeStatus $status, string $ability, string $done): void
    {
        $trade = Trade::findOrFail($tradeId);
        $this->authorize($ability, $trade);

        try {
            app(ResolveTrade::class)->handle($trade, $status);
            session()->flash('mercato', $done);
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    /**
     * I nomi scelti a spunta diventano la forma che l'azione si aspetta.
     *
     * La quantità è sempre 1: il modulo non la chiede ancora, e per gli oggetti
     * che si scambiano davvero — armi, armature, oggetti magici — è quasi
     * sempre giusta.
     *
     * @param  list<string>  $names
     * @return list<array{name: string, qty: int}>
     */
    private function asItems(array $names): array
    {
        return collect($names)
            ->filter()
            ->unique()
            ->map(fn (string $name) => ['name' => $name, 'qty' => 1])
            ->values()
            ->all();
    }

    public function render()
    {
        $character = $this->character();
        $key = $character?->getKey();

        $to = Character::alive()->with('items')->find($this->toCharacterId);

        return view('livewire.market.trades', [
            'character' => $character,
            'others' => Character::alive()
                ->when($key, fn ($q) => $q->whereKeyNot($key))
                ->orderBy('name')
                ->get(),
            'mine' => $character?->items->sortBy('name') ?? collect(),
            /*
             * **Solo la vetrina, non lo zaino.** Di una scheda altrui non si
             * vedono né inventario né oro (P14), e questa pagina lo scavalcava:
             * bastava sceglierlo dalla tendina per vedergli tutto. Adesso si
             * vede quello che lui ha deciso di mostrare, e per il resto si
             * chiede a parole.
             */
            'theirs' => $to?->items->where('tradeable', true)->sortBy('name')->values() ?? collect(),
            // `to` serve a `deliveryProblems()` (P28): la card dice prima del
            // clic se lo scambio non è più eseguibile, e per saperlo guarda
            // anche cosa può dare chi riceve — cioè io.
            'received' => $key
                ? Trade::awaiting(Character::find($key))->with(['from', 'to', 'items'])->get()
                : collect(),
            'sent' => $key
                ? Trade::where('from_character_id', $key)->pending()->with(['to', 'items'])->get()
                : collect(),
            'richiesteArrivate' => $key
                ? TradeRequest::awaiting(Character::find($key))->with('from')->get()
                : collect(),
            'richiesteMandate' => $key
                ? TradeRequest::where('from_character_id', $key)->pending()->with('to')->get()
                : collect(),
            'richiesta' => $this->richiestaAperta
                ? TradeRequest::with('from')->find($this->richiestaAperta)
                : null,
        ])->title('Scambi');
    }
}
