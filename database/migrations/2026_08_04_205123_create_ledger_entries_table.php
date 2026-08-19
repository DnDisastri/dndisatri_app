<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il Registro: ogni movimento di oro e oggetti, con la variazione firmata.
 *
 * È una tabella dedicata e non il registro attività di activitylog, che pure
 * c'è: `gp_delta` finirebbe dentro una colonna JSON, e su quella non si fanno
 * né somme né indici. Qui invece il bilancio di un personaggio è una SUM
 * (§2.2 del piano).
 *
 * Solo scrittura: le righe non si modificano e non si cancellano. Se un valore
 * non torna col registro, qualcosa è stato aggirato — che è esattamente il
 * messaggio scritto nella vecchia interfaccia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            // Chi ha causato il movimento: il giocatore stesso, un DM, o
            // nessuno se l'ha prodotto un'automazione.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 30);

            // Variazione dell'oro: positiva in entrata, negativa in uscita.
            // Zero per i movimenti di soli oggetti.
            $table->integer('gp_delta')->default(0);

            // L'oro del personaggio DOPO il movimento: rende il registro
            // leggibile senza doverlo ricalcolare, e fa saltare all'occhio
            // ogni buco fra una riga e la successiva.
            $table->unsignedInteger('gp_after')->nullable();

            $table->string('message');

            // Per annullare un movimento (D12): i dati strutturati di un
            // acquisto, e il segno di un annullamento già fatto (una volta sola).
            $table->json('details')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');

            $table->timestamps();

            // "Il registro di questo personaggio", dal movimento più recente.
            $table->index(['character_id', 'id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
