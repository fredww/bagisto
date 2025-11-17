<?php
use Illuminate\Support\Facades\Route;

// Fortune Pay public endpoints
Route::prefix('fortune')->middleware(['shop'])->group(function () {
    // 下单
    Route::post('/create-payment', [\App\Http\Controllers\FortunePayController::class, 'createPayment'])
        ->name('fortune.create');

    // 异步通知
    Route::post('/notify', [\App\Http\Controllers\FortunePayController::class, 'notify'])
        ->name('fortune.notify');

    // 同步返回
    Route::get('/return', [\App\Http\Controllers\FortunePayController::class, 'return'])
        ->name('fortune.return');

    // 重定向到网关
    Route::get('/redirect', [\App\Http\Controllers\FortunePayController::class, 'redirect'])
        ->name('fortune.redirect');

    // 查询
    Route::get('/query', [\App\Http\Controllers\FortunePayController::class, 'query'])
        ->name('fortune.query');
});

// Fortune Pay admin config
Route::prefix('admin/fortune-pay')->group(function () {
    Route::get('/config', [\App\Http\Controllers\Admin\FortunePayConfigController::class, 'index'])
        ->name('admin.fortune.config');

    Route::post('/config', [\App\Http\Controllers\Admin\FortunePayConfigController::class, 'update'])
        ->name('admin.fortune.config.update');

    Route::get('/payments', [\App\Http\Controllers\Admin\FortunePayPaymentController::class, 'index'])
        ->name('admin.fortune.payments.index');
});