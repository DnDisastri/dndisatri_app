<?php

namespace App\Livewire\Market;

use App\Actions\Market\AcceptTradeRequest;
use App\Actions\Market\CreateTradeRequest;
use App\Actions\Market\ResolveTrade;
use App\Actions\Market\ResolveTradeRequest;
use App\Actions\Supervision\Supervisor;
use App\Domain\Dnd\Coins;
use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\Character;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\TradeRequest;
use Livewire\Component;

/** Proporre e accettare passano dal Supervisor (vedi `Listings`); rifiutare e ritirare no, non muovono niente. */
class Trades extends Component
{
    use ActsAsCharacter;

    public ?int $toCharacterId = null;

    /** @var list<string> nomi degli oggetti offerti */
    public array $give = [];

    /** @var list<string> nomi degli oggetti chiesti */
    public array $want = [];

    /** @var array<string,int|string|null> */
    public array $giveMonete = [];

    /** @var array<string,int|string|null> */
    public array $wantMonete = [];

    public string $message = '';

    /** Se pieno non parte uno scambio ma una richiesta a parole. */
    public string $chiedo = '';

    public ?int $richiestaAperta = null;

    /** @var list<string> quello che si dà rispondendo a una richiesta */
    public array $offro = [];

    /** @var array<string,int|string|null> */
    public array $offroMonete = [];

    public function mount(): void
    {
        $this->resolveCharacter();
        $this->preselezionaDestinatario();
    }

    /** `?a={id}` dalla vetrina di un altro: valido solo se è vivo e non è il personaggio in uso. */
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

        // Spuntare dalla vetrina è una proposta, scrivere a parole è una richiesta: insieme non si può.
        if ($this->chiedo !== '' && $this->want !== []) {
            $this->addError('scambio', 'Scegli: o spunti qualcosa dalla sua vetrina, o chiedi a parole.');

            return;
        }

        $this->validaMonete(['giveMonete', 'wantMonete']);

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
                giveCp: Coins::fromArray($this->giveMonete)->value(),
                wantCp: Coins::fromArray($this->wantMonete)->value(),
                message: $this->message ?: null,
            );

            $this->reset('give', 'want', 'giveMonete', 'wantMonete', 'message');

            $this->esito($result instanceof SupervisedAction
                ? 'Sei sotto richiamo: la proposta è in attesa che un dungeon master la approvi.'
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
                offeredCp: Coins::fromArray($this->giveMonete)->value(),
                message: $this->message ?: null,
            );

            $this->reset('give', 'want', 'giveMonete', 'wantMonete', 'message', 'chiedo');

            $this->esito('Richiesta inviata: adesso tocca a lui.');
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    // === Le richieste ricevute ===

    public function apriRichiesta(int $requestId): void
    {
        $request = TradeRequest::findOrFail($requestId);
        $this->authorize('accept', $request);

        $this->richiestaAperta = $request->getKey();
        $this->offro = [];
        $this->offroMonete = [];
        $this->resetErrorBag('scambio');
    }

    public function chiudiRichiesta(): void
    {
        $this->richiestaAperta = null;
    }

    /** Dalla richiesta nasce una proposta, che l'altro deve confermare. */
    public function accettaRichiesta(): void
    {
        $request = TradeRequest::findOrFail($this->richiestaAperta);
        $this->authorize('accept', $request);

        $this->validaMonete(['offroMonete']);

        try {
            $result = app(AcceptTradeRequest::class)->handle(
                $request,
                auth()->user(),
                $this->asItems($this->offro),
                Coins::fromArray($this->offroMonete)->value(),
            );

            $this->chiudiRichiesta();

            $this->esito($result instanceof SupervisedAction
                ? 'Sei sotto richiamo: la proposta è in attesa che un dungeon master la approvi.'
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
            $this->esito($done);
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

            $this->esito($result instanceof SupervisedAction
                ? 'Sei sotto richiamo: l\'accettazione è in attesa che un dungeon master la approvi.'
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
            $this->esito($done);
        } catch (MarketException $e) {
            $this->addError('scambio', $e->getMessage());
        }
    }

    /**
     * Quantità sempre 1: il modulo non la chiede ancora.
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

    /**
     * Il `min="0"` del modulo vale solo nel browser.
     *
     * @param  list<string>  $campi
     */
    private function validaMonete(array $campi): void
    {
        $regole = ['nullable', 'integer', 'min:0', 'max:'.Coins::MAX];

        $this->validate(
            array_fill_keys(array_map(fn (string $campo) => "{$campo}.*", $campi), $regole),
            array_merge(...array_map(fn (string $campo) => [
                "{$campo}.*.integer" => 'Le monete si contano a numeri interi.',
                "{$campo}.*.min" => 'Non puoi mettere una cifra negativa.',
                "{$campo}.*.max" => 'Cifra troppo alta: tante monete non esistono.',
            ], $campi)),
        );
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
            // Solo la vetrina: inventario e oro altrui non si vedono.
            'theirs' => $to?->items->where('tradeable', true)->sortBy('name')->values() ?? collect(),
            // `to` serve a `deliveryProblems()`, che controlla anche cosa può dare chi riceve.
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
        ]);
    }
}
