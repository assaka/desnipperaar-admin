<?php

use Illuminate\Support\Facades\Route;

// Public API — called by the static site form.
Route::post('/offerte', [\App\Http\Controllers\Api\OfferteController::class, 'store'])
    ->middleware('throttle:20,1');

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store'])
    ->middleware('throttle:20,1');
