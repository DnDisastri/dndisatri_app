<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** I PNG ricorrenti di una campagna: appunti dei DM, mai visibili ai giocatori. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('location', 150)->nullable();
            $table->string('wants', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['campaign_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npcs');
    }
};
