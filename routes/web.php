<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/orders');
});

// Public offerte-accept (no auth) — token-gated.
Route::get('/offerte/{token}',         [\App\Http\Controllers\QuoteAcceptController::class, 'show'])->name('quote.show');
Route::post('/offerte/{token}/accept', [\App\Http\Controllers\QuoteAcceptController::class, 'accept'])->name('quote.accept');

// Public bon PDF download via signed URL — QR code on the printed bon points here.
Route::get('/b/{bon}/pdf', [\App\Http\Controllers\BonController::class, 'publicPdf'])
    ->middleware('signed')
    ->name('bons.public-pdf');

// Customer self-service reschedule (no auth) — token-gated.
Route::get('/herplan/{token}',  [\App\Http\Controllers\RescheduleController::class, 'show'])->name('reschedule.show');
Route::post('/herplan/{token}', [\App\Http\Controllers\RescheduleController::class, 'store'])->name('reschedule.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/customers',          [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create',   [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers',         [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/search',   [\App\Http\Controllers\CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}',         [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit',    [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
    Route::patch('/customers/{customer}',       [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');

    Route::get('/pricing/quote', [\App\Http\Controllers\PricingController::class, 'quote'])->name('pricing.quote');

    Route::get('/planning',        [\App\Http\Controllers\PlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/events', [\App\Http\Controllers\PlanningController::class, 'events'])->name('planning.events');
    Route::post('/planning/move',  [\App\Http\Controllers\PlanningController::class, 'move'])->name('planning.move');

    Route::get('/offertes', [\App\Http\Controllers\OrderController::class, 'offertes'])->name('offertes.index');

    Route::get('/invoices',                          [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}',                [\App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf',            [\App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/mail',          [\App\Http\Controllers\InvoiceController::class, 'mail'])->name('invoices.mail');
    Route::post('/invoices/{invoice}/mark-paid',     [\App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/transition', [\App\Http\Controllers\OrderController::class, 'transition'])->name('orders.transition');
    Route::post('/orders/{order}/mail', [\App\Http\Controllers\OrderController::class, 'mail'])->name('orders.mail');
    Route::post('/orders/{order}/send-quote', [\App\Http\Controllers\OrderController::class, 'sendQuote'])->name('orders.send-quote');
    Route::post('/orders/{order}/confirm-pickup', [\App\Http\Controllers\OrderController::class, 'confirmPickup'])->name('orders.confirm-pickup');

    Route::get('/bons/{bon}', [\App\Http\Controllers\BonController::class, 'show'])->name('bons.show');
    Route::patch('/bons/{bon}', [\App\Http\Controllers\BonController::class, 'update'])->name('bons.update');
    Route::get('/bons/{bon}/pdf', [\App\Http\Controllers\BonController::class, 'pdf'])->name('bons.pdf');
    Route::get('/bons/{bon}/signature/{role}', [\App\Http\Controllers\BonController::class, 'signature'])
        ->where('role', 'customer|driver')->name('bons.signature');

    Route::post('/orders/{order}/certificate', [\App\Http\Controllers\CertificateController::class, 'generate'])->name('certificates.generate');
    Route::post('/certificates/{certificate}/mail', [\App\Http\Controllers\CertificateController::class, 'mail'])->name('certificates.mail');
    Route::get('/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/pdf', [\App\Http\Controllers\CertificateController::class, 'pdf'])->name('certificates.pdf');

    Route::get('/group-deals',                          [\App\Http\Controllers\GroupDealController::class, 'index'])->name('group-deals.index');
    Route::get('/group-deals/{groupDeal}',              [\App\Http\Controllers\GroupDealController::class, 'show'])->name('group-deals.show');
    Route::post('/group-deals/{groupDeal}/approve',     [\App\Http\Controllers\GroupDealController::class, 'approve'])->name('group-deals.approve');
    Route::post('/group-deals/{groupDeal}/reject',      [\App\Http\Controllers\GroupDealController::class, 'reject'])->name('group-deals.reject');
    Route::post('/group-deals/{groupDeal}/cancel',      [\App\Http\Controllers\GroupDealController::class, 'cancel'])->name('group-deals.cancel');
    Route::post('/group-deals/{groupDeal}/close',       [\App\Http\Controllers\GroupDealController::class, 'manualClose'])->name('group-deals.close');

    Route::get('/drivers', [\App\Http\Controllers\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [\App\Http\Controllers\DriverController::class, 'store'])->name('drivers.store');
    Route::get('/drivers/{driver}/edit', [\App\Http\Controllers\DriverController::class, 'edit'])->name('drivers.edit');
    Route::patch('/drivers/{driver}', [\App\Http\Controllers\DriverController::class, 'update'])->name('drivers.update');
    Route::get('/drivers/{driver}/signature', [\App\Http\Controllers\DriverController::class, 'signature'])->name('drivers.signature');
});

require __DIR__.'/auth.php';
