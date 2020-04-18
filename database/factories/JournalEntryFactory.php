<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Transactions\JournalEntry;
use Ekmungai\IFRS\Models\Transaction;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(JournalEntry::class, function (Faker $faker) {
    return [
        'account_id' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'date'=> Carbon::now(),
        'narration'=> $faker->word,
        'transaction_type'=> Transaction::JN,
        'amount'=> $faker->randomFloat(2),
    ];
});
