<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\Balance;
use Faker\Generator as Faker;

$factory->define(Balance::class, function (Faker $faker) {
    return [
        'entity_id' => factory('IFRS\Models\Entity')->create()->id,
        'exchange_rate_id' => factory('IFRS\Models\ExchangeRate')->create()->id,
        'currency_id' => factory('IFRS\Models\Currency')->create()->id,
        'account_id' => factory('IFRS\Models\Account')->create([
            'account_type' => Account::INVENTORY,
        ])->id,
        'year' => $faker->year(),
        'transaction_date' => Carbon::now()->subYears(1.5),
        'transaction_no' => $faker->word,
        'transaction_type' => $faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
        'reference' => $faker->word,
        'balance_type' =>  $faker->randomElement([
            Balance::DEBIT,
            Balance::CREDIT
        ]),
        'amount' => $faker->randomFloat(2),
    ];
});
