<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('details');
        });

        // Solo per restituirla al venditore se ritira l'annuncio: chi compra non la riceve.
        Schema::table('market_listings', function (Blueprint $table) {
            $table->text('seller_notes')->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('character_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('market_listings', function (Blueprint $table) {
            $table->dropColumn('seller_notes');
        });
    }
};
