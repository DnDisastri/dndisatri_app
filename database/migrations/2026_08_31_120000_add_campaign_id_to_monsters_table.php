<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un mostro può essere legato a una campagna: lo usa solo il DM che la
 * masterizza. `campaign_id` nullo = pubblico, usabile in qualsiasi campagna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('created_by')
                ->constrained('campaigns')
                ->nullOnDelete();
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });
    }
};
