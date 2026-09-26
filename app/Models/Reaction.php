<?php

namespace App\Models;

use App\Enums\Reaction as ReactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Una persona ha reagito a una cosa.
 *
 * Non tiene traccia di niente altro: non c'è una cronologia dei ripensamenti,
 * perché cambiare faccina non è un fatto che qualcuno vorrà rileggere.
 */
#[Fillable(['user_id', 'type'])]
class Reaction extends Model
{
    protected function casts(): array
    {
        return ['type' => ReactionType::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
