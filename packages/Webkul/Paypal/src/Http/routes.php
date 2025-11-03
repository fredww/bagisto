<?php

use Illuminate\Support\Facades\Route;
use Webkul\Paypal\Http\Controllers\ApplePayController;
use Webkul\Paypal\Http\Controllers\SmartButtonController;
use Webkul\Paypal\Http\Controllers\StandardController;

Route::group(['middleware' => ['web']], function () {
    Route::prefix('paypal/standard')->group(function () {
        Route::get('/redirect', [StandardController::class, 'redirect'])->name('paypal.standard.redirect');

        Route::get('/success', [StandardController::class, 'success'])->name('paypal.standard.success');

        Route::get('/cancel', [StandardController::class, 'cancel'])->name('paypal.standard.cancel');
    });

    Route::prefix('paypal/smart-button')->group(function () {
        Route::get('/create-order', [SmartButtonController::class, 'createOrder'])->name('paypal.smart-button.create-order');

        Route::post('/capture-order', [SmartButtonController::class, 'captureOrder'])->name('paypal.smart-button.capture-order');
    });

    Route::prefix('paypal/apple-pay')->group(function () {
        Route::get('/cart', [ApplePayController::class, 'getCartData'])->name('paypal.apple_pay.cart');

        Route::post('/create-order', [ApplePayController::class, 'createOrder'])->name('paypal.apple_pay.create_order');

        Route::post('/capture-order', [ApplePayController::class, 'captureOrder'])->name('paypal.apple_pay.capture_order');

        Route::get('/check-availability', [ApplePayController::class, 'checkAvailability'])->name('paypal.apple_pay.check_availability');
    });
});

Route::post('paypal/standard/ipn', [StandardController::class, 'ipn'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('paypal.standard.ipn');
