<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\TradeDirection;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\Trade;
use App\Notifications\TradeProposed;
use Illuminate\Support\Facades\DB;

/**
 * Propone uno scambio: io do questo e tanto oro, tu dai quello e tanto oro.
 *
 * **Qui non si muove niente.** A differenza degli annunci, dove l'oggetto esce
 * subito dall'inventario ed entra in deposito, una proposta di scambio è solo
 * una domanda: gli inventari si toccano all'accettazione, e lì `AcceptTrade`
 * verifica di nuovo tutto per entrambe le parti.
 *
 * La conseguenza voluta è che si può proporre uno scambio e nel frattempo
 * continuare a usare le proprie cose. La conseguenza scomoda è che una proposta
 * può fallire più tardi, perché nel frattempo l'oggetto è stato venduto. È
 * corretto così, e il messaggio di `AcceptTrade` lo dice per nome.
 *
 * Quello che si controlla adesso è solo ciò che non ha senso nemmeno chiedere:
 * scambiare con sé stessi, o offrire una cosa che non si ha in mano oggi.
 */
final class CreateTrade
{
    /**
     * @param  list<array{name: string, qty?: int}>  $give  gli oggetti offerti
     * @param  list<array{name: string, qty?: int}>  $want  quelli chiesti in cambio
     */
    public function handle(
        Character $from,
        Character $to,
        array $give = [],
        array $want = [],
        int $giveGp = 0,
        int $wantGp = 0,
        ?string $message = null,
    ): Trade {
        if ($from->is($to)) {
            throw new MarketException('Non puoi proporre uno scambio a te stesso.');
        }

        if (! $to->isAlive() || ! $from->isAlive()) {
            throw new MarketException('Non si scambia con un personaggio caduto.');
        }

        if ($giveGp < 0 || $wantGp < 0) {
            throw MarketException::invalidQuantity();
        }

        if ($give === [] && $want === [] && $giveGp === 0 && $wantGp === 0) {
            throw new MarketException('Uno scambio vuoto non si propone.');
        }

        // Il proponente deve avere adesso quello che offre: chiedere una cosa
        // che non si possiede è un errore di compilazione, non un cambiamento
        // di circostanze.
        foreach ($give as $item) {
            $qty = (int) ($item['qty'] ?? 1);

            if ($qty < 1) {
                throw MarketException::invalidQuantity();
            }

            if (! $from->ownsItem($item['name'], $qty)) {
                throw MarketException::itemNotOwned($item['name']);
            }
        }

        if ($from->gp < $giveGp) {
            throw MarketException::notEnoughGold($giveGp, $from->gp);
        }

        return DB::transaction(function () use ($from, $to, $give, $want, $giveGp, $wantGp, $message) {
            $trade = Trade::create([
                'from_character_id' => $from->getKey(),
                'to_character_id' => $to->getKey(),
                'give_gp' => $giveGp,
                'want_gp' => $wantGp,
                'message' => $message,
            ]);

            $this->attach($trade, $give, TradeDirection::Give, $from);
            // Di quello che si chiede si copia solo il nome: la descrizione
            // vera arriverà dall'inventario di chi accetta, che è l'unico posto
            // dove quell'oggetto esiste davvero.
            $this->attach($trade, $want, TradeDirection::Want, $to);

            // Una proposta che nessuno sa di aver ricevuto non serve a niente.
            $to->user()->first()?->notify(new TradeProposed($trade, $from->name));

            return $trade;
        });
    }

    /** @param list<array{name: string, qty?: int}> $items */
    private function attach(Trade $trade, array $items, TradeDirection $direction, Character $owner): void
    {
        foreach ($items as $item) {
            $source = $owner->items()->where('name', $item['name'])->first();

            $trade->items()->create([
                'direction' => $direction,
                'name' => $item['name'],
                'category' => $source?->category,
                'qty' => (int) ($item['qty'] ?? 1),
                'value' => $source?->value ?? 0,
                'details' => $source?->details,
            ]);
        }
    }
}
