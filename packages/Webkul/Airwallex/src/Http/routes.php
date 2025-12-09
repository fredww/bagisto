<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    Route::get('/airwallex/redirect', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'redirect'])
        ->name('airwallex.redirect');

    Route::post('/airwallex/webhook', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'webhook'])
        ->name('airwallex.webhook');

    Route::get('/airwallex/status/{intentId}', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'status'])
        ->name('airwallex.status');

    Route::post('/airwallex/refund', [\Webkul\Airwallex\Http\Controllers\AirwallexController::class, 'refund'])
        ->name('airwallex.refund');
});

