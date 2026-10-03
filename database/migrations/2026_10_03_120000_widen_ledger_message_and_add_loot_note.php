<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il messaggio del Registro può superare i 255 caratteri (un bottino con più
 * oggetti), e la nota del bottino esce dal riassunto per stare in una colonna sua.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->text('message')->change();
        });

        Schema::table('pending_changes', function (Blueprint $table) {
            $table->text('note')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('pending_changes', fn (Blueprint $table) => $table->dropColumn('note'));

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('message')->change();
        });
    }
};
