<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\Category;
use Faker\Generator as Faker;

$factory->define(
    Category::class,
    function (Faker $faker) {
        return [
        'name' => $faker->word,
        'category_type' => $faker->randomElement(
            array_keys(config('ifrs')['accounts'])
        ),
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        ];
    }
);
