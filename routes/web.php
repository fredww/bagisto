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

    // 成功回调
    Route::get('/success', [\App\Http\Controllers\FortunePayController::class, 'success'])
        ->name('fortune.success');

    // 重定向到网关
    Route::get('/redirect', [\App\Http\Controllers\FortunePayController::class, 'redirect'])
        ->name('fortune.redirect');

    // 查询
    Route::get('/query', [\App\Http\Controllers\FortunePayController::class, 'query'])
        ->name('fortune.query');
});

// Asiabill On-Site payment endpoints
Route::group(['middleware' => ['web']], function () {
    Route::prefix('asiabill')->group(function () {
        // 站内支付页面
        Route::get('/onsite', [\App\Http\Controllers\AsiabillController::class, 'onsite'])
            ->name('asiabill.onsite');

        // 获取sessionToken与脚本地址
        Route::get('/session-token', [\App\Http\Controllers\AsiabillController::class, 'sessionToken'])
            ->name('asiabill.session');

        // 发起扣款
        Route::post('/confirm-charge', [\App\Http\Controllers\AsiabillController::class, 'confirmCharge'])
            ->name('asiabill.confirm');

        // 异步通知（无CSRF）
        Route::post('/notify', [\App\Http\Controllers\AsiabillController::class, 'notify'])
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->name('asiabill.notify');

        // 同步返回
        Route::get('/return', [\App\Http\Controllers\AsiabillController::class, 'return'])
            ->name('asiabill.return');

        // 查询本地支付状态
        Route::get('/query', [\App\Http\Controllers\AsiabillController::class, 'query'])
            ->name('asiabill.query');
    });
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
