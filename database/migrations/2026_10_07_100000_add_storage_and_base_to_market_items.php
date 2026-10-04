<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un oggetto ricevuto in baratto entra `in_storage`: non si vede nel negozio
 * finché un DM o un admin non gli dà un prezzo. `base` e `magic_bonus` come su
 * `character_items`, perché l'oggetto torni a chi lo compra com'era.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_items', function (Blueprint $table) {
            $table->string('base', 100)->nullable()->after('name');
            $table->unsignedTinyInteger('magic_bonus')->default(0)->after('base');
            $table->boolean('in_storage')->default(false)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('market_items', function (Blueprint $table) {
            $table->dropColumn(['base', 'magic_bonus', 'in_storage']);
        });
    }
};
