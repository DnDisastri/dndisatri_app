<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taglia, tipo di creatura e grado di sfida. Il grado è testo: ci sono
 * anche 1/8, 1/4 e 1/2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->string('size', 12)->nullable()->after('name');
            $table->string('creature_type', 20)->nullable()->after('size');
            $table->string('challenge', 5)->nullable()->after('creature_type');
        });
    }

    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn(['size', 'creature_type', 'challenge']);
        });
    }
};
