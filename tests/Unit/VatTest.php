<?php

namespace Tests\Unit;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Vat;

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

        $vat = Vat::new($this->faker->word, $this->faker->word, 10);
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
        $vat = Vat::new($this->faker->word, $this->faker->word, 10);
        $vat->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($vat->recycled->first(), $recycled);
    }
}
