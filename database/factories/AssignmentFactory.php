<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        'entity_id' => factory('App\Models\Entity')->create()->id,
        'transaction_id' => factory('App\Models\Transaction')->create()->id,
        'cleared_id' => factory('App\Models\Transaction')->create()->id,
        'cleared_type'=> "App\Models\Transaction",
        'amount' => $faker->randomFloat(2),
    ];
});
