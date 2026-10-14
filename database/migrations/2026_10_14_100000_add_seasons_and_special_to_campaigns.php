<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una campagna può durare più stagioni (`last_season`, vuota = solo `season`).
 * La campagna speciale raccoglie le sessioni speciali: senza titolare, non si chiude.
 * Ogni sessione speciale ha la sua stagione e il suo tipo (apertura, chiusura...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedTinyInteger('last_season')->nullable()->after('season');
            $table->boolean('is_special')->default(false)->after('last_season');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('season')->nullable()->after('title');
            $table->string('special_type', 20)->nullable()->after('season');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['season', 'special_type']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['last_season', 'is_special']);
        });
    }
};
