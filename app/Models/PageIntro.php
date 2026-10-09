<?php

namespace App\Models;

use App\Enums\IntroPage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** L'introduzione di una pagina, riscritta da un admin. Una riga per pagina. */
#[Fillable(['page', 'body'])]
class PageIntro extends Model
{
    use LogsActivity;

    /**
     * I testi salvati, per pagina. Una query sola per richiesta.
     *
     * @return array<string, string>
     */
    public static function bodies(): array
    {
        return once(fn () => static::query()->pluck('body', 'page')->all());
    }

    /** Crea le righe delle pagine che non ce l'hanno ancora, col testo di partenza. */
    public static function fillMissing(): void
    {
        foreach (IntroPage::cases() as $pagina) {
            static::query()->firstOrCreate(['page' => $pagina], ['body' => $pagina->default()]);
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('introduzioni')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'page' => IntroPage::class,
        ];
    }
}
