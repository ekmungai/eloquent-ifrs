<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;

use IFRS\Transactions\JournalEntry;
use IFRS\Models\Transaction;
use IFRS\Models\Account;

$factory->define(
    JournalEntry::class,
    function (Faker $faker) {
        return [
            'account_id' => factory(Account::class)->create()->id,
            'date' => Carbon::now(),
            'narration' => $faker->word,
            'transaction_type' => Transaction::JN,
            'amount' => $faker->randomFloat(2),
        ];
    }
);
