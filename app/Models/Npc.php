<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Un PNG ricorrente di una campagna: appunti dei DM, mai visibili ai giocatori. */
#[Fillable(['name', 'location', 'wants', 'notes', 'is_alive'])]
class Npc extends Model
{
    use HasFactory;

    protected $attributes = ['is_alive' => true];

    protected function casts(): array
    {
        return ['is_alive' => 'boolean'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function scopeSearch(Builder $query, string $testo): void
    {
        $parola = '%'.$testo.'%';

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $parola)
            ->orWhere('location', 'like', $parola)
            ->orWhere('wants', 'like', $parola)
            ->orWhere('notes', 'like', $parola));
    }
}
