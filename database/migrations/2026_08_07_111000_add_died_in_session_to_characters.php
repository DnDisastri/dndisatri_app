<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il collegamento del caduto alla serata in cui è morto.
 *
 * Sta in una migration a parte dalla create di `characters` perché è una
 * chiave esterna verso `game_sessions`, che viene creata dopo: nella create la
 * tabella non esisterebbe ancora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->foreignId('died_in_session_id')
                ->nullable()
                ->after('death_story')
                // La serata si può cancellare senza portarsi via il caduto: si
                // perde il collegamento, non il racconto.
                ->constrained('game_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('died_in_session_id');
        });
    }
};
