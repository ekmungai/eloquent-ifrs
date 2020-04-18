<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Models\Transaction;
use Faker\Generator as Faker;

$factory->define(Transaction::class, function (Faker $faker) {
    return [
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'exchange_rate_id' => factory('Ekmungai\IFRS\Models\ExchangeRate')->create()->id,
        'currency_id' => factory('Ekmungai\IFRS\Models\Currency')->create()->id,
        'account_id' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'date' => $faker->dateTimeThisMonth(),
        'transaction_no' => $faker->word,
        'transaction_type' => $faker->randomElement(array_keys(config('ifrs')['transactions'])),
        'reference' => $faker->word,
        'narration' => $faker->sentence,
        'credited' =>  true,
        'amount' => $faker->randomFloat(2),
    ];
});
