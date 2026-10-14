<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\ImageShrinker;

/**
 * Le immagini caricate si riducono appena salvate, colonna per colonna.
 * Il modello dichiara `imageLimits()`: colonna => lato lungo massimo in pixel.
 */
trait ShrinksImages
{
    /** @return array<string, int> */
    abstract public function imageLimits(): array;

    public static function bootShrinksImages(): void
    {
        static::saved(function (self $model) {
            foreach ($model->imageLimits() as $column => $maxSide) {
                if (($model->wasRecentlyCreated || $model->wasChanged($column)) && filled($model->{$column})) {
                    app(ImageShrinker::class)->shrink('public', $model->{$column}, $maxSide);
                }
            }
        });
    }
}
