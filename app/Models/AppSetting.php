<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** Le impostazioni dell'applicazione che cambiano gli admin dal pannello, una per chiave. */
#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    use LogsActivity;

    /** I giocatori possono creare personaggi con le regole 2024. */
    public const RULES_2024_OPEN = 'rules_2024_open';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('impostazioni')
            ->logOnly(['value'])
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::find($key)?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
