<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['campaign_id', 'uploaded_by', 'title', 'description', 'image_path'])]
class Map extends Model
{
    use HasFactory;

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Una mappa senza campagna vale per tutto il gruppo. */
    public function isGeneral(): bool
    {
        return $this->campaign_id === null;
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function scopeGeneral(Builder $query): void
    {
        $query->whereNull('campaign_id');
    }

    public function scopeForCampaign(Builder $query, Campaign $campaign): void
    {
        $query->where('campaign_id', $campaign->getKey());
    }
}
