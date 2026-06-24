<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reintenta cobros fallidos en campañas completadas o canceladas
Schedule::command('sms:apply-charges')->hourly();

// Cancela y cobra campañas pausadas que llevan más de 7 días sin actividad
Schedule::command('sms:expire-paused')->dailyAt('02:00');
