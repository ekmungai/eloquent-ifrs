<?php

namespace Ekmungai\IFRS;

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
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ifrs.php' => config_path('ifrs.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadFactoriesFrom(__DIR__.'/../database/factories');
    }
}
