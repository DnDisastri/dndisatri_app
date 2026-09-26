<?php

namespace Database\Seeders;

use App\Models\MarketItem;
use Illuminate\Database\Seeder;

/**
 * Il catalogo di partenza del negozio della gilda, dai dati della vecchia
 * applicazione (`catalog.js` → `DEFAULT_MARKET`).
 *
 * È l'unico dato di gioco che finisce su database e non in config/dnd/: gli
 * admin lo modificano, quindi deve vivere nel database.
 *
 * Qui si traduce la vecchia convenzione implicita: `stock: null` significava
 * "scorte infinite", e diventa `is_unlimited = true` (§4.6 del brief).
 */
class MarketSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/market.php') as $entry) {
            MarketItem::updateOrCreate(
                ['name' => $entry['name']],
                [
                    'category' => $entry['category'],
                    'price' => $entry['price'],
                    'is_unlimited' => $entry['stock'] === null,
                    'stock' => $entry['stock'] ?? 0,
                    'details' => $entry['details'] ?? null,
                ],
            );
        }

        $this->command?->info('Catalogo del negozio: '.MarketItem::count().' articoli.');
    }
}
