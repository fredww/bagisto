<?php

namespace Webkul\SecurityPayment\Providers;

use Illuminate\Support\ServiceProvider;

class SecurityPaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'security_payment');
        
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'security_payment');
        
        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/vendor/security_payment'),
        ], 'security-payment-views');
        
        $this->publishes([
            __DIR__ . '/../Resources/lang' => resource_path('lang/vendor/security_payment'),
        ], 'security-payment-lang');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'payment_methods'
        );
        
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );
    }
}