<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ci si prenota alla sessione, con un personaggio; sulla quest si dice solo «mi interessa».
 * Le prenotazioni alle quest diventano interessi, i ritirati si perdono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_players')->default(1);
            $table->unsignedSmallInteger('max_players')->default(6);
            $table->timestamp('players_confirmed_at')->nullable();
        });

        Schema::create('game_session_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('booked');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'user_id']);
            $table->index(['game_session_id', 'status']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->foreignId('game_session_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
        });

        DB::table('quest_user')->where('status', 'withdrawn')->delete();

        Schema::table('quest_user', function (Blueprint $table) {
            $table->dropIndex(['quest_id', 'status']);
        });

        Schema::table('quest_user', function (Blueprint $table) {
            $table->dropColumn(['status', 'decided_at']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['min_participants', 'max_participants', 'night_confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_participants')->default(4);
            $table->unsignedSmallInteger('min_participants')->default(1);
            $table->timestamp('night_confirmed_at')->nullable();
        });

        Schema::table('quest_user', function (Blueprint $table) {
            $table->string('status', 20)->default('booked');
            $table->timestamp('decided_at')->nullable();
            $table->index(['quest_id', 'status']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_session_id');
        });

        Schema::dropIfExists('game_session_bookings');

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['min_players', 'max_players', 'players_confirmed_at']);
        });
    }
};
