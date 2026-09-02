<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quanto il velo copre lo sfondo della pagina campagna: 0 = sfondo pieno,
 * 100 = velo pieno. Prima era fisso all'85% nel template.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedTinyInteger('background_opacity')->default(85)->after('background_path');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('background_opacity');
        });
    }
};
