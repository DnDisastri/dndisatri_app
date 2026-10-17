<?php

namespace Database\Seeders;

use App\Models\BaseItem;
use Illuminate\Database\Seeder;

/** Armi, armature e scudi da `seeders/data/base_items.php`. Non riscrive quelli che ci sono già. */
class BaseItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/base_items.php') as $posizione => $voce) {
            BaseItem::firstOrCreate(['name' => $voce['name']], [...$voce, 'position' => $posizione]);
        }
    }
}
