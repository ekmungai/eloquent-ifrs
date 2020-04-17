<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use App\Models\Entity;
use Faker\Generator as Faker;

$factory->define(
    Entity::class,
    function (Faker $faker) {
        return [
        'name' => $faker->company,
        'currency_id' => factory('App\Models\Currency')->create()->id,
        ];
    }
);
