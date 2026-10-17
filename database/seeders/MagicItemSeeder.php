<?php

namespace Database\Seeders;

use App\Models\MagicItem;
use Illuminate\Database\Seeder;

/** Oggetti magici da `seeders/data/magic_items.php`. Non riscrive quelli che ci sono già. */
class MagicItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/magic_items.php') as $voce) {
            MagicItem::firstOrCreate(['name' => $voce['name'], 'rules_edition' => '2014'], $voce);
        }
    }
}
