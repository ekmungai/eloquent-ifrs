<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\ExchangeRate;
use Faker\Generator as Faker;
use Carbon\Carbon;

$factory->define(
    ExchangeRate::class,
    function (Faker $faker) {
        return [
        'valid_from' => $faker->dateTimeThisMonth(),
        'valid_to' => Carbon::now(),
        'currency_id' => factory('Ekmungai\IFRS\Models\Currency')->create()->id,
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'rate' => $faker->randomFloat(2),
        ];
    }
);
