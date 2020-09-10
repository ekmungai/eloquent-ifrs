<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

use IFRS\Models\Transaction;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;

$factory->define(
    Transaction::class,
    function (Faker $faker) {
        return [
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'currency_id' => factory(Currency::class)->create()->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'transaction_date' => $faker->dateTimeThisMonth(),
            'transaction_no' => $faker->word,
            'transaction_type' => $faker->randomElement(array_keys(config('ifrs')['transactions'])),
            'reference' => $faker->word,
            'narration' => $faker->sentence,
            'credited' =>  true,
        ];
    }
);
