<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** FAQ gestite dagli admin: raggruppate per `category`, ordinate per `position`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();

            // null = voce senza sezione.
            $table->string('category')->nullable();

            $table->string('question');
            $table->longText('answer');

            // Ordine, deciso trascinando le righe nel pannello.
            $table->unsignedInteger('position')->default(0);

            // false = bozza.
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['is_published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
