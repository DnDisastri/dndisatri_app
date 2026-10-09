<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prenotazioni a richiesta: il DM sceglie, il giocatore conferma entro una
 * scadenza, chi resta fuori può fare da riserva. Gli ospiti chiedono da un
 * modulo pubblico e gestiscono il posto da un link personale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_session_bookings', function (Blueprint $table) {
            $table->timestamp('offer_expires_at')->nullable()->after('decided_at');
            $table->timestamp('reserve_asked_at')->nullable()->after('offer_expires_at');
            $table->string('guest_email', 255)->nullable()->after('guest_character');
            $table->string('guest_phone', 30)->nullable()->after('guest_email');
            // Gli ospiti aggiunti dal DM arrivano da Instagram o Telegram: lì li si ricontatta.
            $table->string('guest_social', 80)->nullable()->after('guest_phone');
            $table->uuid('guest_token')->nullable()->unique()->after('guest_phone');
        });

        // La conferma della sessione intera non esiste più: ogni posto si conferma da sé.
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('players_confirmed_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
        });

        DB::table('game_session_bookings')->whereIn('status', ['booked', 'waiting'])->update(['status' => 'requested']);
    }

    public function down(): void
    {
        DB::table('game_session_bookings')->whereIn('status', ['unverified', 'offered', 'reserve', 'expired'])->update(['status' => 'withdrawn']);
        DB::table('game_session_bookings')->where('status', 'requested')->update(['status' => 'booked']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->timestamp('players_confirmed_at')->nullable();
        });

        Schema::table('game_session_bookings', function (Blueprint $table) {
            $table->dropUnique(['guest_token']);
            $table->dropColumn(['offer_expires_at', 'reserve_asked_at', 'guest_email', 'guest_phone', 'guest_social', 'guest_token']);
        });
    }
};
