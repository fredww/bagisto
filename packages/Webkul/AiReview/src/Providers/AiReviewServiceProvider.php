<?php

namespace Webkul\AiReview\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

class AiReviewServiceProvider extends ServiceProvider
{
    /**
     * 中文：注册服务提供者，合并配置（菜单与系统设置）。
     *
     * Register the service provider and merge package configuration.
     */
    public function register(): void
    {
        // Merge admin menu configuration
        $this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'menu.admin');

        // Merge system configuration under 'core'
        $this->mergeConfigFrom(__DIR__ . '/../Config/system.php', 'core');
    }

    /**
     * 中文：引导包资源，加载视图/语言，注册路由。
     *
     * Bootstrap package resources, load views/translations, and register routes.
     */
    public function boot(): void
    {
        // Load views and translations for the package
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'ai_review');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'ai_review');

        // Register admin routes with proper middlewares and prefix
        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(function () {
                Route::group([
                    'middleware' => ['admin', NoCacheMiddleware::class],
                    'prefix'     => config('app.admin_url', 'admin'),
                ], function () {
                    require __DIR__ . '/../Routes/admin.php';
                });
            });
    }
}