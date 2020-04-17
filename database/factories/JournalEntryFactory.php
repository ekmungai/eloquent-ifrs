<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Transactions\JournalEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(JournalEntry::class, function (Faker $faker) {
    return [
        'account_id' => factory('App\Models\Account')->create()->id,
        'date'=> Carbon::now(),
        'narration'=> $faker->word,
        'transaction_type'=> Transaction::JN,
        'amount'=> $faker->randomFloat(2),
    ];
});
