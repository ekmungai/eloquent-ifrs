<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Ekmungai\IFRS\Models\RecycledObject;
use Faker\Generator as Faker;

$factory->define(
    RecycledObject::class,
    function (Faker $faker) {
        return [
        'user_id' => factory('Ekmungai\IFRS\Models\User')->create()->id,
        'entity_id' => factory('Ekmungai\IFRS\Models\Entity')->create()->id,
        'recyclable_id' => factory('Ekmungai\IFRS\Models\User')->create()->id,
        'recyclable_type' => 'Ekmungai\IFRS\Models\User',
        ];
    }
);
