<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\Entity;
use Faker\Generator as Faker;

$factory->define(
    Entity::class,
    function (Faker $faker) {
        return [
        'name' => $faker->company,
        'currency_id' => factory('Ekmungai\IFRS\Models\Currency')->create()->id,
        ];
    }
);
