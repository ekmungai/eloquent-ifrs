<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\RecycledObject;
use IFRS\User;
use IFRS\Models\Vat;

class VatTest extends TestCase
{
    /**
     * Test Vat model Entity Scope.
     *
     * @return void
     */
    public function testVatEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $vat = new Vat(
            [
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            ]
        );
        $vat->attributes();
        $vat->save();

        $this->assertEquals(count(Vat::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Vat::all()), 0);
    }

    /**
     * Test Vat Model recylcling
     *
     * @return void
     */
    public function testVatRecycling()
    {
        $vat = new Vat(
            [
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            ]
        );
        $vat->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($vat->recycled->first(), $recycled);
    }
}
