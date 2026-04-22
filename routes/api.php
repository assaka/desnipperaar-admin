<?php

use Illuminate\Support\Facades\Route;

// Direct order (transparent price, customer accepts prijs on submit).
Route::post('/order', [\App\Http\Controllers\Api\OrderController::class, 'store'])
    ->middleware('throttle:20,1');

// Custom / maatwerk quote request — admin replies with tailored offer.
Route::post('/offerte', [\App\Http\Controllers\Api\OfferteController::class, 'store'])
    ->middleware('throttle:20,1');

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store'])
    ->middleware('throttle:20,1');

Route::get('/eligibility/kennismaking', [\App\Http\Controllers\Api\EligibilityController::class, 'kennismaking'])
    ->middleware('throttle:60,1');

// GitHub push-webhook / manual deploy trigger. Auth via DEPLOY_HOOK_SECRET.
Route::post('/deploy', [\App\Http\Controllers\DeployController::class, 'handle'])
    ->middleware('throttle:10,1');
