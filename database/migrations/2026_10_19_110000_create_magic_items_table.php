<?php

use Database\Seeders\MagicItemSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rules_edition', 4)->default('2014');
            $table->string('rarity', 12)->default('uncommon');
            $table->boolean('requires_attunement')->default(false);
            $table->string('category')->default('Oggetti Magici');
            $table->string('base')->nullable();
            $table->unsignedTinyInteger('magic_bonus')->default(0);
            $table->json('effects')->nullable();
            $table->unsignedInteger('value_cp')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['name', 'rules_edition']);
        });

        (new MagicItemSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_items');
    }
};
