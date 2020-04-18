<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Models\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'transaction_id' => factory('Ekmungai\IFRS\Models\Transaction')->create()->id,
        'cleared_id' => factory('Ekmungai\IFRS\Models\Transaction')->create()->id,
        'cleared_type'=> "Ekmungai\IFRS\Models\Transaction",
        'amount' => $faker->randomFloat(2),
    ];
});
