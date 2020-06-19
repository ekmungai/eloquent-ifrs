<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;

use IFRS\Models\Vat;
use IFRS\Models\Account;

$factory->define(
    Vat::class,
    function (Faker $faker) {
        return [
            'name' => $faker->name,
            'code' => $faker->randomLetter(),
            'rate' => $faker->randomDigit(),
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::CONTROL
            ])->id,
        ];
    }
);
