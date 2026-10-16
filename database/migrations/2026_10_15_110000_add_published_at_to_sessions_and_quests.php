<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le bozze: `published_at` null = la vedono solo DM e admin. Quello che esiste
 * già è pubblicato da quando è stato creato.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['game_sessions', 'quests'] as $tabella) {
            Schema::table($tabella, fn (Blueprint $table) => $table->timestamp('published_at')->nullable()->index());

            DB::table($tabella)->update(['published_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
        }
    }

    public function down(): void
    {
        foreach (['game_sessions', 'quests'] as $tabella) {
            Schema::table($tabella, function (Blueprint $table) {
                $table->dropIndex(['published_at']);
                $table->dropColumn('published_at');
            });
        }
    }
};
