<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le serate di gioco di una campagna: quando si gioca e cos'è successo.
 *
 * Si chiama `game_sessions` e non `sessions` perché quel nome è già occupato
 * da Laravel per le sessioni di login (SESSION_DRIVER=database).
 *
 * Sono tenute separate dalle quest di proposito: una quest può durare più
 * serate, e recap e presenze appartengono alla serata. Se stessero sulla quest,
 * una quest lunga tre sessioni avrebbe un solo recap e un solo elenco di
 * presenti, che sarebbe falso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            // Progressivo dentro la campagna: "Sessione 12".
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('title')->nullable();

            // Quando si gioca. Nel futuro è il calendario dei tavoli aperti,
            // nel passato è la data della serata.
            $table->dateTime('played_at');

            // Il resoconto, scritto dopo. Lo leggono tutti.
            $table->text('recap')->nullable();
            $table->foreignId('recap_written_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recap_written_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // La Regia del DM: gli appunti privati e l'ordine d'iniziativa.
            $table->text('dm_notes')->nullable();
            $table->json('initiative')->nullable();

            $table->timestamps();

            // "Le prossime sessioni", e lo storico di una campagna.
            $table->index(['campaign_id', 'played_at']);
            $table->index('played_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
