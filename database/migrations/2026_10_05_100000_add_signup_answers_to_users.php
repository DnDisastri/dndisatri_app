<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Le risposte date all'iscrizione. Nulle per chi si era iscritto prima delle domande. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('discovery_source')->nullable()->after('approved_at');
            $table->boolean('played_before')->nullable()->after('discovery_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['discovery_source', 'played_before']));
    }
};
