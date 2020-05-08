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
        'year' => $faker->year,
        'entity_id' => factory('IFRS\Models\Entity')->create()->id,
            'status' => ReportingPeriod::OPEN,
        ];
    }
);
