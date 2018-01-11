<?php
namespace Tartan\Larapay;

use Illuminate\Support\ServiceProvider;
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
