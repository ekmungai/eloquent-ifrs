<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Balance;
use Faker\Generator as Faker;

$factory->define(Balance::class, function (Faker $faker) {
    return [
        'entity_id' => factory('App\Models\Entity')->create()->id,
        'exchange_rate_id' => factory('App\Models\ExchangeRate')->create()->id,
        'currency_id' => factory('App\Models\Currency')->create()->id,
        'account_id' => factory('App\Models\Account')->create([
            'account_type' => Account::INVENTORY,
        ])->id,
        'year' => $faker->year(),
        'transaction_no' => $faker->word,
        'transaction_type' => $faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
        'reference' => $faker->word,
        'balance_type' =>  $faker->randomElement([
            Balance::D,
            Balance::C
        ]),
        'amount' => $faker->randomFloat(2),
    ];
});
