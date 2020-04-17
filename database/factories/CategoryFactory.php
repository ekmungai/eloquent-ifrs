<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use App\Models\Category;
use Faker\Generator as Faker;

$factory->define(
    Category::class,
    function (Faker $faker) {
        return [
        'name' => $faker->word,
        'category_type' => $faker->randomElement(
            array_keys(config('ifrs')['accounts'])
        ),
        'entity_id' => factory('App\Models\Entity')->create()->id,
        ];
    }
);
