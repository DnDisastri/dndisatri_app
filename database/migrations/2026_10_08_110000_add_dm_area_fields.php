<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sulla serata le ricompense già date (per non darle due volte senza saperlo).
 * Sulla campagna la nota di passaggio fra DM e la percentuale del listino.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->json('rewards')->nullable()->after('recap_written_at');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('handover_notes')->nullable();
            $table->foreignId('handover_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handover_updated_at')->nullable();
            $table->smallInteger('price_modifier')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('rewards');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handover_updated_by');
            $table->dropColumn(['handover_notes', 'handover_updated_at', 'price_modifier']);
        });
    }
};
