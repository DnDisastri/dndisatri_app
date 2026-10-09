<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un posto può essere di un ospite senza account, aggiunto da un DM: niente
 * giocatore, solo un nome. Quando si registra, il DM collega il posto al suo account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_session_bookings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name', 80)->nullable()->after('character_id');
            $table->string('guest_character', 80)->nullable()->after('guest_name');
            $table->string('guest_note', 255)->nullable()->after('guest_character');
            $table->boolean('guest_attended')->default(false)->after('guest_note');
            $table->foreignId('added_by')->nullable()->after('guest_attended')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_session_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_by');
            $table->dropColumn(['guest_name', 'guest_character', 'guest_note', 'guest_attended']);
        });
    }
};
