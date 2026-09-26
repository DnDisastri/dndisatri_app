<?php

use App\Enums\QuestSeatStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chi partecipa a una quest.
 *
 * I giocatori si iscrivono e si disiscrivono da soli finché ci sono posti
 * liberi: è l'unica cosa che possono modificare senza chiedere il permesso.
 *
 * Il legame è con l'utente e non col personaggio, come nella vecchia
 * applicazione: alla sessione partecipa la persona. Se un domani servirà
 * sapere con quale personaggio ci è andata, è una colonna in più qui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Lo stato del posto: prenotato, confermato, in lista d'attesa…
            $table->string('status', 20)->default(QuestSeatStatus::Booked->value);

            $table->timestamp('joined_at')->nullable();
            // Quando il posto è stato confermato, o quando ci si è ritirati.
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            // Ci si iscrive una volta sola: il posto occupato è uno.
            $table->unique(['quest_id', 'user_id']);
            $table->index('user_id');
            // "I prenotati di questa quest", la query di ogni pagina che la mostra.
            $table->index(['quest_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_user');
    }
};
