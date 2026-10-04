<?php

namespace Database\Seeders;

use App\Models\MarketItem;
use Illuminate\Database\Seeder;

/**
 * Il catalogo di partenza: sta nel database e non in config/dnd/ perché gli
 * admin lo modificano. Nei dati `stock: null` vuol dire scorte infinite.
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
                    // I dati del catalogo sono in mo.
                    'price_cp' => (int) round($entry['price'] * 100),
                    'is_unlimited' => $entry['stock'] === null,
                    'stock' => $entry['stock'] ?? 0,
                    'details' => $entry['details'] ?? null,
                ],
            );
        }

        $this->command?->info('Catalogo del negozio: '.MarketItem::count().' articoli.');
    }
}
