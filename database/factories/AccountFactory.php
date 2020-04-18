<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\Account;
use Faker\Generator as Faker;

$factory->define(
    Account::class,
    function (Faker $faker) {
        return [
        'name' => $faker->name,
        'description' => $faker->sentence,
        'account_type' => $faker->randomElement(array_keys(config('ifrs')['accounts'])),
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'category_id' => factory('Ekmungai\IFRS\Models\Category')->create()->id,
        'currency_id' => factory('Ekmungai\IFRS\Models\Currency')->create()->id,
        'code' => $faker->randomDigit,
        ];
    }
);
