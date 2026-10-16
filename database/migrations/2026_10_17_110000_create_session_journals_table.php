<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Il diario del giocatore: una nota per ogni sessione giocata da un personaggio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->unique(['character_id', 'game_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_journals');
    }
};
