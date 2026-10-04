<?php

declare(strict_types=1);

use App\Enums\SupervisedActionType;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// I dati di prima delle monete: prezzi e valori in mo, borse fatte di solo oro.
it('converte i dati esistenti senza toccare le borse', function () {
    $migrazione = require database_path('migrations/2026_10_04_100000_introduce_coins.php');
    $migrazione->down();

    $pg = Character::factory()->create(['gp' => 120]);
    $utente = User::factory()->create();

    $articolo = DB::table('market_items')->insertGetId([
        'name' => 'Corda', 'category' => 'Varie', 'price' => 15, 'is_unlimited' => true, 'stock' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $oggetto = DB::table('character_items')->insertGetId([
        'character_id' => $pg->id, 'name' => 'Spada', 'qty' => 1, 'value' => 15,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $riga = DB::table('ledger_entries')->insertGetId([
        'character_id' => $pg->id, 'action' => 'buy', 'gp_delta' => -15, 'gp_after' => 120, 'message' => 'Acquisto',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $bottino = DB::table('pending_changes')->insertGetId([
        'character_id' => $pg->id, 'requested_by' => $utente->id, 'type' => 'loot', 'status' => 'pending',
        'grant_gp' => 40, 'grant_items' => json_encode([['name' => 'Pozione', 'qty' => 1, 'value' => 50]]),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $azione = DB::table('supervised_actions')->insertGetId([
        'user_id' => $utente->id, 'type' => SupervisedActionType::ListingCreation->value, 'status' => 'pending',
        'payload' => json_encode(['character_id' => $pg->id, 'name' => 'Spada', 'qty' => 1, 'price' => 20]),
        'summary' => 'Vende', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $migrazione->up();

    expect(DB::table('characters')->where('id', $pg->id)->value('gp'))->toBe(120)
        ->and(DB::table('market_items')->where('id', $articolo)->value('price_cp'))->toBe(1500)
        ->and(DB::table('character_items')->where('id', $oggetto)->value('value_cp'))->toBe(1500)
        ->and(DB::table('ledger_entries')->where('id', $riga)->value('cp_delta'))->toBe(-1500)
        ->and(json_decode(DB::table('ledger_entries')->where('id', $riga)->value('coins_after'), true))->toBe(['gp' => 120])
        ->and(json_decode(DB::table('pending_changes')->where('id', $bottino)->value('grant_coins'), true))->toBe(['gp' => 40])
        ->and(json_decode(DB::table('pending_changes')->where('id', $bottino)->value('grant_items'), true)[0]['value_cp'])->toBe(5000)
        ->and(json_decode(DB::table('supervised_actions')->where('id', $azione)->value('payload'), true)['price_cp'])->toBe(2000);
});
