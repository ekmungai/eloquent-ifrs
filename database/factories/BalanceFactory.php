<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;

use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;

$factory->define(
    Balance::class,
    function (Faker $faker) {
        return [
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'currency_id' => factory(Currency::class)->create()->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::INVENTORY,
                'category_id' => null
            ])->id,
            'reporting_period_id' => 1,
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
    }
);
