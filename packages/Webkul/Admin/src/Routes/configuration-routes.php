<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\ConfigurationController;

/**
 * Configuration routes.
 */
Route::get('configuration/search', [ConfigurationController::class, 'search'])->name('admin.configuration.search');

// Specific edit route outside the prefix group to handle edit/sales/payment pattern
Route::get('configuration/edit/{slug?}/{slug2?}', [ConfigurationController::class, 'index'])->name('admin.configuration.edit');

Route::controller(ConfigurationController::class)->prefix('configuration/{slug?}/{slug2?}')->group(function () {

    Route::get('', 'index')->name('admin.configuration.index');

    Route::post('', 'store')->name('admin.configuration.store');

    Route::get('{path}', 'download')->name('admin.configuration.download');
});
