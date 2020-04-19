<?php

namespace Ekmungai\IFRS\Tests;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as Orchestra;

use Ekmungai\IFRS\IFRSServiceProvider;
use Ekmungai\IFRS\Models\ReportingPeriod;
use Ekmungai\IFRS\Models\User;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->faker = Faker::create();

        $this->be(factory(User::class)->create());
        $this->period = factory(ReportingPeriod::class)->create([
            "year" => date("Y"),
        ]);
    }

    /**
     * Add the package provider
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [IFRSServiceProvider::class];
    }
}
