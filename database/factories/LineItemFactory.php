<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use IFRS\Models\LineItem;
use Faker\Generator as Faker;

$factory->define(LineItem::class, function (Faker $faker) {
    return [
        'entity_id' => factory('IFRS\Models\Entity')->create()->id,
        'vat_id' => factory('IFRS\Models\Vat')->create()->id,
        'transaction_id' => factory('IFRS\Models\Transaction')->create()->id,
        'account_id' => factory('IFRS\Models\Account')->create()->id,
        'vat_account_id' => factory('IFRS\Models\Account')->create()->id,
        'description' => $faker->sentence,
        'quantity' => $faker->randomNumber(),
        'amount' => $faker->randomFloat(2),
    ];
});
