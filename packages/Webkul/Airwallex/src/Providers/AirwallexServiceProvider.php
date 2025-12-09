<?php

namespace Webkul\Airwallex\Providers;

use Illuminate\Support\ServiceProvider;

class AirwallexServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'airwallex');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'airwallex');

        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/vendor/airwallex'),
        ], 'airwallex-views');

        $this->publishes([
            __DIR__ . '/../Resources/lang' => resource_path('lang/vendor/airwallex'),
        ], 'airwallex-lang');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/paymentmethods.php', 'payment_methods');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/system.php', 'core');
    }
}

