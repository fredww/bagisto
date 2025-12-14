<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::group(['middleware' => ['web']], function () {
    Route::get('/airwallex/redirect', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'redirect'])
        ->name('airwallex.redirect');

    Route::post('/airwallex/webhook', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'webhook'])
        ->name('airwallex.webhook')
        ->withoutMiddleware(ValidateCsrfToken::class);

    Route::get('/airwallex/callback', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'callback'])
        ->name('airwallex.callback');

    Route::get('/airwallex/status/{intentId}', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'status'])
        ->name('airwallex.status');

    Route::post('/airwallex/refund', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'refund'])
        ->name('airwallex.refund');
});
