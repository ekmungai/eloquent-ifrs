<?php

namespace Tests;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use App\Models\ReportingPeriod;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->prepareForTests();

        $this->be(factory(User::class)->create());
        $this->period = factory(ReportingPeriod::class)->create([
            "year" => date("Y"),
        ]);
    }

    /**
     * Migrate the database
     */
    private function prepareForTests()
    {
        Config::set('database.default', 'sqlite');
        Artisan::call('migrate:refresh');
    }
}
