<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use IFRS\Models\ReportingPeriod;
use Faker\Generator as Faker;

$factory->define(
    ReportingPeriod::class,
    function (Faker $faker) {
        return [
        'period_count' => $faker->randomDigit,
        'calendar_year' => $faker->year,
            'status' => ReportingPeriod::OPEN,
        ];
    }
);
