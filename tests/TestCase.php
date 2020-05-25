<?php

namespace IFRS\Tests;

use Faker\Factory as Faker;

use Orchestra\Testbench\TestCase as Orchestra;

use IFRS\IFRSServiceProvider;
use IFRS\Models\ReportingPeriod;
use IFRS\User;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {

        parent::setUp();

        Config::set('ifrs.user_model', User::class);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->faker = Faker::create();

        $this->be(factory(User::class)->create());
        $this->period = factory(ReportingPeriod::class)->create(
            [
            "year" => date("Y"),
            ]
        );
    }

    /**
     * Add the package provider
     *
     * @param  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [IFRSServiceProvider::class];
    }
}
