<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use App\Models\ReportingPeriod;
use Faker\Generator as Faker;

$factory->define(
    ReportingPeriod::class,
    function (Faker $faker) {
        return [
        'period_count' => $faker->randomDigit,
        'year' => $faker->year,
        'entity_id' => factory('App\Models\Entity')->create()->id,
        ];
    }
);
