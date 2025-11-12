<?php

use Illuminate\Support\Facades\Route;

/**
 * 中文：AI Review 管理端路由定义。
 * Purpose: Define admin routes for AI Review features.
 */
Route::prefix('ai-review')->group(function () {
    // Admin AI Review page with datagrid and form
    Route::get('/', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'index'])
        ->name('admin.ai_review.index');

    // Generate reviews for a single product
    Route::post('/generate', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'generate'])
        ->name('admin.ai_review.generate');

    // Mass generate reviews for selected products
    Route::post('/mass-generate', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'massGenerate'])
        ->name('admin.ai_review.mass_generate');

    // Health check route
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    })->name('admin.ai_review.ping');
});