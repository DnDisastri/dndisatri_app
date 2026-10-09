<?php

use App\Actions\Sessions\ExpireSessionOffers;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Le offerte di posto non confermate in tempo: il posto torna libero e il DM lo sa.
Artisan::command('dndisastri:scadi-offerte', function (ExpireSessionOffers $scadi) {
    $this->info('Offerte scadute: '.$scadi->handle());
})->purpose('Fa scadere le offerte di posto non confermate in tempo');

Schedule::command('dndisastri:scadi-offerte')->everyTenMinutes()->withoutOverlapping();
