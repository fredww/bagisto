<?php

use Illuminate\Support\Facades\Route;
use Webkul\SecurityPayment\Http\Controllers\SecurityPaymentController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {
    Route::prefix('security-payment')->name('security_payment.')->group(function () {
        Route::get('/redirect', [SecurityPaymentController::class, 'redirect'])->name('redirect');
        Route::post('/notify', [SecurityPaymentController::class, 'notify'])->name('notify');
        Route::get('/return', [SecurityPaymentController::class, 'return'])->name('return');
        Route::get('/success', [SecurityPaymentController::class, 'success'])->name('success');
        Route::get('/failure', [SecurityPaymentController::class, 'failure'])->name('failure');
        Route::get('/error', [SecurityPaymentController::class, 'error'])->name('error');
    });
});