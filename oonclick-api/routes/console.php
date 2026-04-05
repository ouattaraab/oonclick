<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Traitement des jobs en file d'attente — remplace le worker persistant sur
// Hostinger (hébergement mutualisé sans process daemons).
// --stop-when-empty : s'arrête dès que la queue est vide.
// --max-time=55     : force l'arrêt après 55 s pour libérer avant la prochaine minute.
// withoutOverlapping: évite les exécutions concurrentes si la cron est lente.
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();

Schedule::command('oonclick:daily-stats')->dailyAt('08:00');
Schedule::command('queue:prune-failed --hours=72')->daily();

// Prix hebdomadaires du classement — chaque lundi à 00:05
Schedule::command('oonclick:award-weekly-prizes')->weeklyOn(1, '00:05');

// Auto-expiry des campagnes (date dépassée ou quota de vues atteint)
Schedule::command('oonclick:expire-campaigns')->everyFiveMinutes();
