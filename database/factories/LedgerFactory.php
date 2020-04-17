<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Balance;
use App\Models\Ledger;
use Faker\Generator as Faker;

$factory->define(Ledger::class, function (Faker $faker) {
    return [
        'entity_id' => factory('App\Models\Entity')->create()->id,
        'transaction_id' => factory('App\Models\Transaction')->create()->id,
        'vat_id' => factory('App\Models\Vat')->create()->id,
        'post_account' => factory('App\Models\Account')->create()->id,
        'folio_account' => factory('App\Models\Account')->create()->id,
        'line_item_id' => factory('App\Models\LineItem')->create()->id,
        'date' => $faker->dateTimeThisMonth(),
        'entry_type' => $faker->randomElement([
            Balance::D,
            Balance::C
        ]),
        'amount' => $faker->randomFloat(2),
    ];
});
