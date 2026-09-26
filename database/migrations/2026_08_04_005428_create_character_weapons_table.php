<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le armi della scheda, con il loro bonus di attacco.
 *
 * Sono distinte dagli oggetti in inventario: qui sta come si combatte con
 * quell'arma (caratteristica, bonus magico, danni), lì il possesso e il valore
 * di mercato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_weapons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            // Caratteristica usata per il tiro: di norma Forza o Destrezza.
            $table->string('attack_ability', 3)->default('str');

            // Bonus magico dell'arma: +1, +2, +3.
            $table->unsignedTinyInteger('weapon_bonus')->default(0);

            // Espressione dei danni, es. "1d8+3". Resta testo: non la calcoliamo.
            $table->string('damage')->nullable();

            $table->timestamps();

            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_weapons');
    }
};
