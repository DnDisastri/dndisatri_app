<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Passi del tutorial: titolo e testo dal pannello, illustrazione da un elenco fisso (App\Enums\TutorialIllustration). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorial_steps', function (Blueprint $table) {
            $table->id();

            // Quale disegno abbinare al passo.
            $table->string('illustration');

            $table->string('title');
            $table->text('body');

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['is_published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorial_steps');
    }
};
