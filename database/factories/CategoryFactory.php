<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use IFRS\Models\Category;
use Faker\Generator as Faker;

$factory->define(
    Category::class,
    function (Faker $faker) {
        return [
            'name' => $faker->words(3, true),
            'category_type' => $faker->randomElement(
                array_keys(config('ifrs')['accounts'])
            ),
        ];
    }
);
