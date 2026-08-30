<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le classi di un personaggio (D14, D17).
 *
 * Fino a qui la classe era una colonna sola, e il multiclasse non era
 * rappresentabile: un Guerriero 3 / Mago 2 non esisteva.
 *
 * **Qui sta la verità**, ma su `characters` restano una copia del livello
 * totale e della classe principale. È una denormalizzazione voluta: `class` e
 * `level` sono letti da mezza applicazione e servono in SQL per ordinare ed
 * elencare la Gilda. La si tiene onesta scrivendola in un punto solo, nella
 * stessa transazione che tocca queste righe.
 *
 * La classe **principale** è quella presa per prima, e non è un dettaglio: da
 * lei, e solo da lei, arrivano i tiri salvezza competenti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            $table->string('class');
            $table->string('subclass')->nullable();
            $table->unsignedTinyInteger('level')->default(1);

            // La prima presa. I tiri salvezza vengono da qui.
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            // Una classe non si prende due volte: si sale di livello in quella
            // che si ha già.
            $table->unique(['character_id', 'class']);
            $table->index(['character_id', 'is_primary']);
        });

        // I personaggi che ci sono diventano monoclasse, con quello che hanno.
        $existing = Schema::getConnection()->table('characters')
            ->select('id', 'class', 'subclass', 'level')
            ->get();

        foreach ($existing as $character) {
            Schema::getConnection()->table('character_classes')->insert([
                'character_id' => $character->id,
                'class' => $character->class,
                'subclass' => $character->subclass,
                'level' => $character->level,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('character_classes');
    }
};
