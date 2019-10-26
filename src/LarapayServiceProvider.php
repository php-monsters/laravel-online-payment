<?php

namespace Tartan\Larapay;

use Illuminate\Support\ServiceProvider;
use Tartan\Larapay\Console\InstallCommand;
use Tartan\Larapay\Factory;
use Tartan\Larapay\Contracts\LarapayTransaction as LarapayTransactionContract;
use Tartan\Larapay\Models\LarapayTransaction;

class LarapayServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerResources();
        $this->registerPublishing();
        $this->registerModelBindings();

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function register()
    {
        $this->app->singleton('larapay', function ($app) {
            return new Factory;
        });
    }

    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../views/', 'larapay');

        $this->publishes([
            __DIR__ . '/../translations/' => resource_path('lang/vendor/larapay'),
        ], 'translations');

        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'larapay');
    }

    protected function registerPublishing()
    {

        $this->publishes([
            __DIR__ . '/../config/larapay.php' => config_path('larapay.php')
        ], 'config');

        $this->publishes([
            __DIR__ . '/../views/' => resource_path('/views/vendor/larapay'),
        ], 'views');


        $this->publishes([
            __DIR__ . '/../database/migrations/create_larapay_transaction_table.php.stub' => database_path('migrations/' . date('Y_m_d_His',
                    time()) . '_create_larapay_transaction_table.php'),
        ], 'migrations');

    }


    protected function registerModelBindings()
    {
        $this->app->bind(LarapayTransactionContract::class, LarapayTransaction::class);
    }

}
