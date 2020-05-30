<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use IFRS\Models\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        'entity_id' => factory('IFRS\Models\Entity')->create()->id,
        'assignment_date' => $faker->dateTimeThisMonth(),
        'transaction_id' => factory('IFRS\Models\Transaction')->create()->id,
        'cleared_id' => factory('IFRS\Models\Transaction')->create()->id,
        'cleared_type'=> "IFRS\Models\Transaction",
        'amount' => $faker->randomFloat(2),
    ];
});
