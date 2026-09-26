<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli oggetti magici che alterano una caratteristica.
 *
 * I punteggi base del personaggio non vengono mai toccati: questi effetti si
 * applicano sopra al momento del calcolo (App\Domain\Dnd\AbilityScores), così
 * togliendo l'oggetto tutto torna com'era senza dover ricostruire nulla.
 *
 * Conseguenza sui punti ferita: se l'effetto tocca la Costituzione, i PF
 * massimi efficaci cambiano di (delta modificatore x livello) finché resta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_item_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            // L'effetto segue l'oggetto: se la riga d'inventario se ne va, il
            // bonus la segue. Nullo per benedizioni e maledizioni del DM, che
            // non hanno un oggetto da indossare.
            $table->foreignId('character_item_id')
                ->nullable()
                ->constrained('character_items')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('ability', 3);

            // 'set'   → porta il punteggio al valore, ma solo se è un miglioramento
            // 'bonus' → somma algebrica, accetta valori negativi
            $table->string('mode', 10);
            $table->smallInteger('value');

            $table->timestamps();

            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_item_effects');
    }
};
