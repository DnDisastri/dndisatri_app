<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il documento più ricco della vecchia applicazione, normalizzato.
 *
 * Cosa NON viene portato, di proposito:
 *
 * - `combat.ac` e `combat.initiative`: erano salvati e mai letti, perché si
 *   ricalcolano sempre. Qui non esistono (App\Domain\Dnd\ArmorClass).
 * - `proficiencyBonus`: si deriva dal livello.
 * - `characterName` e gli altri campi denormalizzati: ci pensano le relazioni.
 *
 * I punteggi di caratteristica sono colonne e non JSON perché ogni calcolo di
 * gioco parte da lì. Tiri salvezza e abilità restano JSON: si leggono e si
 * scrivono sempre in blocco, e nessuno ci fa query sopra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('class');
            $table->string('subclass')->nullable();
            $table->string('race');
            $table->string('background')->nullable();

            // Chi è, in due righe: la parte pubblica che leggono gli altri.
            $table->text('story')->nullable();
            // Il percorso sul disco pubblico. Nullo finché non c'è una foto.
            $table->string('photo_path')->nullable();

            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedTinyInteger('hit_die')->default(8);
            $table->unsignedTinyInteger('hit_dice_used')->default(0);

            // Punteggi BASE: creazione più ASI. Gli oggetti magici non li
            // toccano mai, si applicano sopra al momento del calcolo.
            foreach (['str', 'dex', 'con', 'int', 'wis', 'cha'] as $ability) {
                $table->unsignedTinyInteger($ability)->default(10);
            }

            $table->decimal('speed', 4, 1)->default(9);
            $table->unsignedSmallInteger('hp_max')->default(1);
            $table->smallInteger('hp_current')->default(1);
            $table->unsignedSmallInteger('hp_temp')->default(0);

            $table->unsignedInteger('gp')->default(0);

            // { "str": true, "con": true, ... } — competenza nei tiri salvezza.
            $table->json('saving_throws')->nullable();
            // { "stealth": "expert", "arcana": "proficient", ... }
            $table->json('skills')->nullable();
            // { "1": 2, "pact": 1 } — slot consumati, azzerati dai riposi.
            $table->json('spell_slots_used')->nullable();

            $table->string('spell_ability', 3)->nullable();

            $table->text('species_traits')->nullable();
            $table->text('class_features')->nullable();
            $table->text('background_feature')->nullable();
            $table->text('subclass_features')->nullable();
            $table->text('notes')->nullable();

            // La morte è irreversibile e la decide solo il DM.
            $table->timestamp('died_at')->nullable();
            $table->text('death_story')->nullable();
            // I tiri contro morte: due file di tre pallini.
            $table->unsignedTinyInteger('death_save_successes')->default(0);
            $table->unsignedTinyInteger('death_save_failures')->default(0);
            // Il collegamento alla serata in cui è caduto sta in una migration
            // a parte: `game_sessions` viene creata dopo questa tabella.

            $table->timestamps();

            // "I personaggi vivi di questo giocatore": serve al vincolo di un
            // solo personaggio vivo alla volta e alla Gilda.
            $table->index(['user_id', 'died_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
