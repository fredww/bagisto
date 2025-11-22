<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Communication\EmailLogController;

Route::controller(EmailLogController::class)->prefix('communication/email-logs')->group(function () {
    Route::get('', 'index')->name('admin.communication.email_logs.index');
    Route::post('resend/{id}', 'resend')->name('admin.communication.email_logs.resend');
});

