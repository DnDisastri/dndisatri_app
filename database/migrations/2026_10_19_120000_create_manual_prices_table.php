<?php

use Database\Seeders\ManualPriceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_prices', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('name');
            $table->unsignedInteger('price_cp');
            $table->unsignedSmallInteger('position')->default(99);
            $table->timestamps();

            $table->unique(['category', 'name']);
        });

        // Il listino prima stava in config/dnd/prices.php: arriva con la migrazione, così in produzione c'è subito.
        (new ManualPriceSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_prices');
    }
};
