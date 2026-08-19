<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chi c'era davvero a una serata.
 *
 * È un dato diverso dagli iscritti a una quest: ci si può iscrivere e poi non
 * presentarsi. Qui la riga esiste solo se la persona ha giocato, e la segna il
 * DM a fine sessione.
 *
 * Serve ad assegnare bottini e passaggi di livello a chi ha effettivamente
 * partecipato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Con quale personaggio c'era: «c'era Grimm», non «c'era Marco».
            // Il personaggio cancellato non porta via la presenza.
            $table->foreignId('character_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->unique(['game_session_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_user');
    }
};
