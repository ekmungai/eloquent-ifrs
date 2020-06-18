<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;

use IFRS\Models\Entity;
use IFRS\Models\Currency;

$factory->define(
    Entity::class,
    function (Faker $faker) {
        return [
            'name' => $faker->company,
            'currency_id' => factory(Currency::class)->create()->id,
            'multi_currency' => $faker->boolean(),
        ];
    }
);
