<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * I combattimenti escono dalla serata: se ne preparano più d'uno, e il legame
 * con la serata resta facoltativo, solo come storico. L'iniziativa salvata sulle
 * serate diventa un combattimento collegato, poi la colonna sparisce.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('status', 20)->default('prepared');
            $table->unsignedSmallInteger('round')->default(1);
            $table->string('turn_id', 40)->nullable();
            $table->json('combatants')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });

        DB::table('game_sessions')->whereNotNull('initiative')->orderBy('id')->each(function ($serata) {
            $dati = json_decode((string) $serata->initiative, true) ?: [];
            $combattenti = $dati['combattenti'] ?? $dati['ordine'] ?? [];

            if ($combattenti === []) {
                return;
            }

            $giocata = $serata->played_at !== null && $serata->played_at < now()->toDateTimeString();

            DB::table('encounters')->insert([
                'campaign_id' => $serata->campaign_id,
                'game_session_id' => $serata->id,
                'title' => 'Combattimento della sessione'.($serata->number !== null ? " {$serata->number}" : ''),
                'status' => $giocata ? 'ended' : 'prepared',
                'round' => (int) ($dati['round'] ?? 1),
                'turn_id' => $dati['turnoId'] ?? null,
                'combatants' => json_encode(array_values($combattenti)),
                'created_by' => $serata->created_by,
                'ended_at' => $giocata ? $serata->played_at : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('initiative');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->json('initiative')->nullable();
        });

        Schema::dropIfExists('encounters');
    }
};
