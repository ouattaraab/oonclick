<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('oonclick:daily-stats')->dailyAt('08:00');
Schedule::command('queue:prune-failed --hours=72')->daily();

// Prix hebdomadaires du classement — chaque lundi à 00:05
Schedule::command('oonclick:award-weekly-prizes')->weeklyOn(1, '00:05');

// Auto-expiry des campagnes (date dépassée ou quota de vues atteint)
Schedule::command('oonclick:expire-campaigns')->everyFiveMinutes();
