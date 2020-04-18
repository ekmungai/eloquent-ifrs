<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Ekmungai\IFRS\Models\LineItem;
use Faker\Generator as Faker;

$factory->define(LineItem::class, function (Faker $faker) {
    return [
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'vat_id' => factory('Ekmungai\IFRS\Models\Vat')->create()->id,
        'transaction_id' => factory('Ekmungai\IFRS\Models\Transaction')->create()->id,
        'account_id' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'vat_account_id' => factory('Ekmungai\IFRS\Models\Account')->create()->id,
        'description' => $faker->sentence,
        'quantity' => $faker->randomNumber(),
        'amount' => $faker->randomFloat(2),
    ];
});
