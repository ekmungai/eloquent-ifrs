<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use IFRS\Models\RecycledObject;
use Faker\Generator as Faker;

$factory->define(
    RecycledObject::class,
    function (Faker $faker) {
        return [
        'user_id' => factory('IFRS\User')->create()->id,
        'recyclable_id' => factory('IFRS\User')->create()->id,
        'recyclable_type' => 'IFRS\User',
        ];
    }
);
