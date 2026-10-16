<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** La nota del giocatore su una sessione giocata: la leggono lui, i DM e gli admin. */
#[Fillable(['character_id', 'game_session_id', 'body'])]
class SessionJournal extends Model
{
    /** Quanto può essere lunga una nota. */
    public const MAX = 3600;

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
