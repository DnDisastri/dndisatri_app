<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chi conduce una sessione, oltre al titolare della campagna: co-master,
 * sostituti, i DM dei tavoli di una multitavolo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_dm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['game_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_dm');
    }
};
