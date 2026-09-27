<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le sottorazze: drow, elfo dei boschi, nano delle colline.
 *
 * A differenza delle sottoclassi non sono solo un nome: portano bonus alle
 * caratteristiche che si sommano a quelli della razza, e a volte una velocità
 * diversa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subraces', function (Blueprint $table) {
            $table->id();

            // Il nome della razza come sta in config/dnd/species.php: le razze
            // restano quelle e non si aggiungono dal pannello.
            $table->string('race');
            $table->string('name');
            $table->text('description')->nullable();

            // Come gli `asi` della razza: {"wis": 1}. Si sommano a quelli suoi.
            $table->json('asi')->nullable();

            // Solo chi cambia il passo rispetto alla razza, come l'elfo dei
            // boschi. Nullo vuol dire «quella della razza».
            $table->decimal('speed', 4, 1)->nullable();

            $table->text('traits')->nullable();

            $table->boolean('is_homebrew')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['race', 'name']);
            $table->index(['race', 'position']);
        });

        Schema::table('characters', function (Blueprint $table) {
            // Nulla per chi esiste già, e per le razze che non ne hanno.
            $table->string('subrace')->nullable()->after('race');
        });
    }

    public function down(): void
    {
        Schema::table('characters', fn (Blueprint $table) => $table->dropColumn('subrace'));
        Schema::dropIfExists('subraces');
    }
};
