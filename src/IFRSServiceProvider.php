<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS;

use Illuminate\Support\ServiceProvider;

class IFRSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ifrs.php', 'ifrs');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ifrs.php' => app()->configPath('ifrs.php'),
        ]);

		
		if (config('ifrs.load_migrations', true)) {
			$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		}

		if ($this->app->runningInConsole() && config('ifrs.load_factories', true)) {
			$this->loadFactoriesFrom(__DIR__ . '/../database/factories');
		}				
    }
}
