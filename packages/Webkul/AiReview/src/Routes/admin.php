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
    // Start AI generation job for multiple products with config
    Route::post('/generate-batch', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'generateBatch'])
        ->name('admin.ai_review.generate_batch');

    // Job status and preview
    Route::get('/jobs/{id}', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'jobStatus'])
        ->name('admin.ai_review.job_status');

    Route::get('/jobs/{id}/export', [\Webkul\AiReview\Http\Controllers\AiReviewController::class, 'exportJob'])
        ->name('admin.ai_review.job_export');

    // AI Models CRUD
    Route::prefix('models')->group(function () {
        Route::get('/', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'index'])
            ->name('admin.ai_review.models.index');

        Route::get('/create', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'create'])
            ->name('admin.ai_review.models.create');

        Route::post('/', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'store'])
            ->name('admin.ai_review.models.store');

        Route::get('/{id}/edit', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'edit'])
            ->name('admin.ai_review.models.edit');

        Route::put('/{id}', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'update'])
            ->name('admin.ai_review.models.update');

        Route::delete('/{id}', [\Webkul\AiReview\Http\Controllers\AiModelController::class, 'destroy'])
            ->name('admin.ai_review.models.destroy');
    });
});