<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Le regole della casa per i DM: raggruppate per `category`, ordinate per `position`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_rules', function (Blueprint $table) {
            $table->id();

            // null = regola senza sezione.
            $table->string('category')->nullable();

            $table->string('title');
            $table->text('body');

            // Ordine, deciso trascinando le righe nel pannello.
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_rules');
    }
};
