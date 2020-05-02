<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use IFRS\Models\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        'entity_id' => factory('IFRS\Models\Entity')->create()->id,
        'transaction_id' => factory('IFRS\Models\Transaction')->create()->id,
        'cleared_id' => factory('IFRS\Models\Transaction')->create()->id,
        'cleared_type'=> "IFRS\Models\Transaction",
        'amount' => $faker->randomFloat(2),
    ];
});
