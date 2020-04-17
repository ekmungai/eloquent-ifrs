<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use App\Models\Account;
use Faker\Generator as Faker;

$factory->define(
    Account::class,
    function (Faker $faker) {
        return [
        'name' => $faker->name,
        'description' => $faker->sentence,
        'account_type' => $faker->randomElement(array_keys(config('ifrs')['accounts'])),
        'entity_id' => factory('App\Models\Entity')->create()->id,
        'category_id' => factory('App\Models\Category')->create()->id,
        'currency_id' => factory('App\Models\Currency')->create()->id,
        'code' => $faker->randomDigit,
        ];
    }
);
