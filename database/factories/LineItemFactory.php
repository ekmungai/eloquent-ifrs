<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

use IFRS\Models\LineItem;
use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\Vat;

$factory->define(
    LineItem::class,
    function (Faker $faker) {
        return [
            'vat_id' => factory(Vat::class)->create()->id,
            'transaction_id' => factory(Transaction::class)->create()->id,
            'account_id' => factory(Account::class)->create()->id,
            'narration' => $faker->sentence,
            'quantity' => $faker->randomNumber(),
            'amount' => $faker->randomFloat(2),
        ];
    }
);
