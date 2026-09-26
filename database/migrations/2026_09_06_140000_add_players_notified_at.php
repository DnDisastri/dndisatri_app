<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Segna quando i giocatori sono già stati avvisati, per non avvisarli due volte. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('players_notified_at')->nullable()->after('published_at');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->timestamp('players_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('players_notified_at'));
        Schema::table('game_sessions', fn (Blueprint $table) => $table->dropColumn('players_notified_at'));
    }
};
