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

// Daily 08:00 Europe/Amsterdam: on this week's random discount weekday, activate
// DSDAG35 and e-mail subscribers; on other days it self-skips. Idempotent.
Schedule::command('desnipperaar:dag-announce')
    ->dailyAt('08:00')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();

// Dagelijks 03:00 Europe/Amsterdam: conceptfacturen voor lopende abonnementen,
// vooruit per kalendermaand. Draait elke dag en niet alleen op de 1e, zodat een
// gemiste dag of een abonnement dat midden in de maand start vanzelf bijtrekt.
// Idempotent via de unique index op (order_id, period_start).
Schedule::command('subscriptions:invoice')
    ->dailyAt('03:00')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();

// Dagelijks 02:30 Europe/Amsterdam: rollend venster van 90 dagen aan ophalingen
// voor lopende abonnementen, als losse orders met een datum zodat ze op het
// planbord staan. Idempotent via de unique index op
// (subscription_order_id, subscription_scheduled_for).
Schedule::command('subscriptions:plan')
    ->dailyAt('02:30')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();

// Dagelijks 09:00 Europe/Amsterdam: klanten met een Vast- of Jaartermijn die
// over ongeveer een maand afloopt krijgen de verlengmail. Draait vóór de
// omzetting naar maandelijks (die zit in subscriptions:invoice), zodat niemand
// een termijnwissel meemaakt zonder vooraf gevraagd te zijn.
Schedule::command('subscriptions:renewal-notice')
    ->dailyAt('09:00')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();

Schedule::command('mail:fetch-inbound')
    ->everyFiveMinutes()
    ->withoutOverlapping();
