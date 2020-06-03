<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use IFRS\Models\Balance;
use IFRS\Models\Ledger;
use Faker\Generator as Faker;

$factory->define(Ledger::class, function (Faker $faker) {
    return [
        'transaction_id' => factory('IFRS\Models\Transaction')->create()->id,
        'vat_id' => factory('IFRS\Models\Vat')->create()->id,
        'post_account' => factory('IFRS\Models\Account')->create()->id,
        'folio_account' => factory('IFRS\Models\Account')->create()->id,
        'line_item_id' => factory('IFRS\Models\LineItem')->create()->id,
        'date' => $faker->dateTimeThisMonth(),
        'entry_type' => $faker->randomElement([
            Balance::DEBIT,
            Balance::CREDIT
        ]),
        'amount' => $faker->randomFloat(2),
    ];
});
