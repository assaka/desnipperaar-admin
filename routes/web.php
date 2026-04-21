<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/orders');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/customers',          [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create',   [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers',         [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/search',   [\App\Http\Controllers\CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}',         [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit',    [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
    Route::patch('/customers/{customer}',       [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');

    Route::get('/pricing/quote', [\App\Http\Controllers\PricingController::class, 'quote'])->name('pricing.quote');

    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/transition', [\App\Http\Controllers\OrderController::class, 'transition'])->name('orders.transition');

    Route::get('/bons/{bon}', [\App\Http\Controllers\BonController::class, 'show'])->name('bons.show');
    Route::get('/bons/{bon}/pdf', [\App\Http\Controllers\BonController::class, 'pdf'])->name('bons.pdf');

    Route::post('/orders/{order}/certificate', [\App\Http\Controllers\CertificateController::class, 'generate'])->name('certificates.generate');
    Route::post('/certificates/{certificate}/mail', [\App\Http\Controllers\CertificateController::class, 'mail'])->name('certificates.mail');
    Route::get('/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/pdf', [\App\Http\Controllers\CertificateController::class, 'pdf'])->name('certificates.pdf');

    Route::get('/drivers', [\App\Http\Controllers\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [\App\Http\Controllers\DriverController::class, 'store'])->name('drivers.store');
});

require __DIR__.'/auth.php';
