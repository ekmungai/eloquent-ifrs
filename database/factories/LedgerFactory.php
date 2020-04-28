<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Ledger;
use Faker\Generator as Faker;

$factory->define(Ledger::class, function (Faker $faker) {
    return [
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'transaction_id' => factory('Ekmungai\IFRS\Models\Transaction')->create()->id,
        'vat_id' => factory('Ekmungai\IFRS\Models\Vat')->create()->id,
        'post_account' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'folio_account' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'line_item_id' => factory('Ekmungai\IFRS\Models\LineItem')->create()->id,
        'date' => $faker->dateTimeThisMonth(),
        'entry_type' => $faker->randomElement([
            Balance::DEBIT,
            Balance::CREDIT
        ]),
        'amount' => $faker->randomFloat(2),
    ];
});
