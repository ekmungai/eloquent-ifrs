<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use IFRS\Models\Transaction;
use Faker\Generator as Faker;

$factory->define(Transaction::class, function (Faker $faker) {
    return [
        'exchange_rate_id' => factory('IFRS\Models\ExchangeRate')->create()->id,
        'currency_id' => factory('IFRS\Models\Currency')->create()->id,
        'account_id' => factory('IFRS\Models\Account')->create()->id,
        'transaction_date' => $faker->dateTimeThisMonth(),
        'transaction_no' => $faker->word,
        'transaction_type' => $faker->randomElement(array_keys(config('ifrs')['transactions'])),
        'reference' => $faker->word,
        'narration' => $faker->sentence,
        'credited' =>  true,
    ];
});
