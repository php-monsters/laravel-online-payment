<?php
namespace Tartan\Larapay;

use Illuminate\Support\ServiceProvider;
use Tartan\Larapay\Console\InstallCommand;
use Tartan\Larapay\Factory;

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
		$this->publishes([
			__DIR__ . '/../config/larapay.php' => config_path('larapay.php')
		], 'config');

		$this->publishes([
			__DIR__ . '/../views/' => resource_path('/views/vendor/larapay'),
		], 'views');

		$this->loadViewsFrom(__DIR__ . '/../views/', 'larapay');

		$this->publishes([
			__DIR__ . '/../translations/' => resource_path('lang/vendor/larapay'),
		], 'translations');

		$this->loadTranslationsFrom(__DIR__ . '/../translations', 'larapay');

		//TODO publish migrations

        $this->publishes([
            __DIR__.'/../database/migrations/create_larapay_transaction_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_larapay_transaction_table.php'),
        ], 'migrations');
        //TODO publish controller

        //TODO publish routs


        // commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }


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
}
