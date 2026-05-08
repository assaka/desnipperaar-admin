<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 02:00 Europe/Amsterdam: close any open deal whose join cutoff has passed
// and materialize one Order per participant. Idempotent.
Schedule::command('group-deals:close')
    ->dailyAt('02:00')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();
