<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rules_edition', 4)->default('2014');
            $table->string('prerequisite')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_homebrew')->default(true);
            $table->timestamps();

            $table->unique(['name', 'rules_edition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feats');
    }
};
