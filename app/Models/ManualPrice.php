<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** Una voce del listino del Manuale del DM. Non tocca i prezzi veri dell'Emporio. */
#[Fillable(['category', 'name', 'price_cp', 'position'])]
class ManualPrice extends Model
{
    use LogsActivity;

    protected $attributes = ['position' => 99];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalogo')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'price_cp' => 'integer',
            'position' => 'integer',
        ];
    }
}
