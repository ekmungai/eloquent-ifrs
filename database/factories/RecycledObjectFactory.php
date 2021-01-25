<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;

use IFRS\Models\RecycledObject;
use IFRS\User;

$factory->define(
    RecycledObject::class,
    function (Faker $faker) {
        return [
            'user_id' => factory(User::class)->create()->id,
            'recyclable_id' => factory(User::class)->create()->id,
            'recyclable_type' => User::class,
        ];
    }
);
