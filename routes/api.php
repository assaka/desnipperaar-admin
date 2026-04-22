<?php

use Illuminate\Support\Facades\Route;

// Public API — called by the static site form.
Route::post('/offerte', [\App\Http\Controllers\Api\OfferteController::class, 'store'])
    ->middleware('throttle:20,1');

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store'])
    ->middleware('throttle:20,1');

// GitHub push-webhook / manual deploy trigger. Auth via DEPLOY_HOOK_SECRET.
Route::post('/deploy', [\App\Http\Controllers\DeployController::class, 'handle'])
    ->middleware('throttle:10,1');
