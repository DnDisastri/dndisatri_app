<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le quest, raggruppate per campagna.
 *
 * Il ciclo di vita ha tre stati e due uscite, entrambe irreversibili:
 *
 *   attiva ─┬─► completata  (andata a buon fine)
 *           └─► chiusa      (abbandonata)
 *
 * Sono stati distinti e vanno tenuti separati anche se finiscono entrambi nel
 * Libro Mastro: «l'abbiamo portata a termine» e «l'abbiamo mollata» non sono
 * la stessa cosa per chi rilegge l'archivio.
 *
 * Due timestamp nullable invece di un enum più una data: lo stato È la data, e
 * non ci sono due valori da tenere allineati. `campaignTitle` della vecchia
 * collection sparisce: era una copia che si disallineava (§8.7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();

            // Chi affida l'incarico non sta qui: è il capogilda del tavolo,
            // uno per campagna (vedi `campaigns.quest_giver`). Tenerne una
            // copia su ogni quest sarebbe la denormalizzazione che il brief
            // segnala al §8.7.

            $table->text('description');
            $table->text('setting')->nullable();
            $table->text('rewards')->nullable();
            // Ricompense strutturate, accanto alla frase libera.
            $table->unsignedInteger('reward_gold')->nullable();
            $table->json('reward_items')->nullable();

            $table->string('difficulty', 20);
            // Quest di campagna o one-shot: null nessuno, default campaign.
            $table->string('type', 20)->default('campaign');
            $table->unsignedSmallInteger('max_participants')->default(4);
            // Il minimo informa, non vieta: una quest ha senso anche per uno.
            $table->unsignedSmallInteger('min_participants')->default(1);
            // «La serata si fa», dichiarato dal DM. Sta qui e non si deduce dai
            // posti confermati.
            $table->timestamp('night_confirmed_at')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Il racconto di com'è andata, anche a incarico chiuso.
            $table->text('outcome_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // "Le quest attive di questa campagna", la query della pagina.
            $table->index(['campaign_id', 'completed_at', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
