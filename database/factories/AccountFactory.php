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

        $type = $this->faker->randomElement($types);
        return [
            'name' => $faker->name,
            'account_type' => $type,
            'category_id' => factory('IFRS\Models\Category')->create([
                'category_type' => $type,
            ])->id,
            'code' => $faker->randomDigit,
        ];
    }
);
