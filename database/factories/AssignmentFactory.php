<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

use IFRS\Models\Assignment;
use IFRS\Models\Transaction;

$factory->define(
    Assignment::class,
    function (Faker $faker) {
        return [
            'assignment_date' => $faker->dateTimeThisMonth(),
            'transaction_id' => factory(Transaction::class)->create()->id,
            'cleared_id' => factory(Transaction::class)->create()->id,
            'cleared_type' => Transaction::class,
            'amount' => $faker->randomFloat(2),
        ];
    }
);
