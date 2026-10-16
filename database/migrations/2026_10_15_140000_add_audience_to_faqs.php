<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dove si vede una FAQ: nella pagina pubblica, in quella interna o in tutte e due. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->boolean('show_public')->default(true)->after('is_published');
            $table->boolean('show_private')->default(true)->after('show_public');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['show_public', 'show_private']);
        });
    }
};
