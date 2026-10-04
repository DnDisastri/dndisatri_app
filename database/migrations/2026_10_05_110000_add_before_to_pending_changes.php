<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Com'era la scheda quando la richiesta è stata decisa: dopo, leggerla dal
 * personaggio darebbe già i valori nuovi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_changes', function (Blueprint $table) {
            $table->json('before')->nullable()->after('diff');
        });
    }

    public function down(): void
    {
        Schema::table('pending_changes', fn (Blueprint $table) => $table->dropColumn('before'));
    }
};
