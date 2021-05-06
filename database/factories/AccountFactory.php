<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use IFRS\Models\Account;
use Faker\Generator as Faker;

$factory->define(
    Account::class,
    function (Faker $faker) {
        $types = array_keys(config('ifrs')['accounts']);
        unset($types[3]);
        return [
            'name' => $faker->name,
            'account_type' => $faker->randomElement($types),
            'category_id' => factory('IFRS\Models\Category')->create()->id,
            'code' => $faker->randomDigit,
        ];
    }
);
