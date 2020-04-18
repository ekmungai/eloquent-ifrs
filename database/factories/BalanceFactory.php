<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Transaction;
use Ekmungai\IFRS\Models\Balance;
use Faker\Generator as Faker;

$factory->define(Balance::class, function (Faker $faker) {
    return [
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'exchange_rate_id' => factory('Ekmungai\IFRS\Models\ExchangeRate')->create()->id,
        'currency_id' => factory('Ekmungai\IFRS\Models\Currency')->create()->id,
        'account_id' => factory('Ekmungai\IFRS\Models\Account')->create([
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
