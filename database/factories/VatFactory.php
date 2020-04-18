<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\Vat;
use Faker\Generator as Faker;

$factory->define(
    Vat::class,
    function (Faker $faker) {
        return [
        'name' => $faker->name,
        'code' => $faker->randomLetter(),
        'rate' => $faker->randomDigit(),
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        ];
    }
);
