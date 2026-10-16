<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La sessione da cui arriva una richiesta (bottino, livello): avvisa i suoi DM. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_changes', function (Blueprint $table) {
            $table->foreignId('game_session_id')->nullable()->after('requested_by')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pending_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_session_id');
        });
    }
};
