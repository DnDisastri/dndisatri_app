<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le sottoclassi, che fino a qui stavano in config/dnd/subclasses.php.
 *
 * Sono globali e non legate a una campagna: i personaggi non appartengono a
 * una campagna, quindi al momento della scelta non ci sarebbe niente da
 * guardare per decidere se una sottoclasse è ammessa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subclasses', function (Blueprint $table) {
            $table->id();

            // Il nome della classe come sta in config/dnd/classes.php: le
            // classi restano dodici e non si aggiungono dal pannello.
            $table->string('class');
            $table->string('name');
            $table->text('description')->nullable();

            // Cavaliere Mistico e Furfante Arcano lanciano pur stando in una
            // classe che non lancia. Era una lista in config: senza questa
            // colonna una homebrew che lancia non potrebbe esistere.
            $table->boolean('third_caster')->default(false);

            $table->boolean('is_homebrew')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['class', 'name']);
            $table->index(['class', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subclasses');
    }
};
