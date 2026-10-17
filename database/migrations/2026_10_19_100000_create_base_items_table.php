<?php

use Database\Seeders\BaseItemSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('kind', 10);
            $table->string('rules_edition', 4)->default('2014');

            $table->string('damage', 20)->nullable();
            $table->string('damage_type', 20)->nullable();
            $table->string('ability', 3)->nullable();
            $table->json('properties')->nullable();

            $table->string('armor_type', 10)->nullable();
            $table->unsignedTinyInteger('armor_base')->nullable();
            $table->unsignedTinyInteger('dex_cap')->nullable();
            $table->boolean('stealth_disadvantage')->default(false);
            $table->unsignedTinyInteger('strength_required')->nullable();

            $table->unsignedTinyInteger('shield_bonus')->nullable();

            $table->unsignedInteger('price_cp')->nullable();
            $table->unsignedSmallInteger('position')->default(99);
            $table->timestamps();
        });

        // Il catalogo prima stava in config/dnd/combat.php: arriva con la migrazione, così in produzione c'è subito.
        (new BaseItemSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('base_items');
    }
};
