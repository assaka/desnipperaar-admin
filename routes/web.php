<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/orders');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/transition', [\App\Http\Controllers\OrderController::class, 'transition'])->name('orders.transition');

    Route::get('/bons/{bon}', [\App\Http\Controllers\BonController::class, 'show'])->name('bons.show');
    Route::get('/bons/{bon}/pdf', [\App\Http\Controllers\BonController::class, 'pdf'])->name('bons.pdf');

    Route::get('/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/pdf', [\App\Http\Controllers\CertificateController::class, 'pdf'])->name('certificates.pdf');

    Route::get('/drivers', [\App\Http\Controllers\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [\App\Http\Controllers\DriverController::class, 'store'])->name('drivers.store');
});

require __DIR__.'/auth.php';
