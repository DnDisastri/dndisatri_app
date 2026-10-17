<?php

namespace Database\Seeders;

use App\Models\ManualPrice;
use Illuminate\Database\Seeder;

/** Il listino del Manuale da `seeders/data/manual_prices.php`. Non riscrive le voci che ci sono già. */
class ManualPriceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/manual_prices.php') as $categoria => $voci) {
            foreach (array_keys($voci) as $posizione => $nome) {
                ManualPrice::firstOrCreate(
                    ['category' => $categoria, 'name' => $nome],
                    ['price_cp' => $voci[$nome], 'position' => $posizione],
                );
            }
        }
    }
}
