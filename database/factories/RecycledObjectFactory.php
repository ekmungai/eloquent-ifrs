<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use App\Models\RecycledObject;
use Faker\Generator as Faker;

$factory->define(
    RecycledObject::class,
    function (Faker $faker) {
        return [
        'user_id' => factory('App\Models\User')->create()->id,
        'entity_id' => factory('App\Models\Entity')->create()->id,
        'recyclable_id' => factory('App\Models\User')->create()->id,
        'recyclable_type' => 'App\Models\User',
        ];
    }
);
