<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli effetti stanno su `character_item_effects`, legati alla riga dello zaino:
 * quando l'oggetto esce dallo zaino la riga sparisce e gli effetti con lei.
 * Qui se ne tiene una copia, che si ricrea su chi riceve l'oggetto.
 */
return new class extends Migration
{
    private const TABELLE = ['market_listings', 'trade_items', 'market_items'];

    public function up(): void
    {
        foreach (self::TABELLE as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->json('effects')->nullable()->after('magic_bonus');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELLE as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->dropColumn('effects');
            });
        }
    }
};
