<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `base` è la chiave del catalogo di combattimento («Armatura a Piastre») per gli
 * oggetti con un nome loro; `magic_bonus` è il +N. Annunci e scambi li copiano,
 * o l'oggetto li perderebbe passando di mano.
 */
return new class extends Migration
{
    private const TABELLE = ['character_items', 'market_listings', 'trade_items'];

    public function up(): void
    {
        foreach (self::TABELLE as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->string('base', 100)->nullable()->after('name');
                $table->unsignedTinyInteger('magic_bonus')->default(0)->after('base');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELLE as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->dropColumn(['base', 'magic_bonus']);
            });
        }
    }
};
