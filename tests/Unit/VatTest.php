<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\RecycledObject;
use IFRS\User;
use IFRS\Models\Vat;
// use Carbon\Carbon;
// use IFRS\Exceptions\VatPeriodOverlap;

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

        $this->assertEquals(
            $vat->identifier(),
            'VAT: '.$vat->name.'('.$vat->code.') at '.number_format($vat->rate, 2).'%'
        );
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

//     /**
//      * Test Vat Open Period Overlap
//      *
//      * @return void
//      */
//     public function testOpenVatPeriodOverlap()
//     {
//         factory(Vat::class)->create([
//             'valid_from' => Carbon::now()->subMonth(),
//             'valid_to' => null
//         ]);

//         $vat = new Vat(
//             [
//                 'name' => $this->faker->name,
//                 'code' => $this->faker->randomLetter(),
//                 'rate' => 10,
//                 'valid_from' => Carbon::now()
//             ]
//             );

//         $this->expectException(VatPeriodOverlap::class);
//         $this->expectExceptionMessage('A VAT record already exists for that period');

//         $vat->save();
//     }

//     /**
//      * Test Vat Closed Period Overlap
//      *
//      * @return void
//      */
//     public function testClosedVatPeriodOverlap()
//     {
//         factory(Vat::class)->create([
//             'valid_from' => Carbon::now()->subMonths(2),
//             'valid_to' => Carbon::now()
//         ]);

//         $vat = new Vat(
//             [
//                 'name' => $this->faker->name,
//                 'code' => $this->faker->randomLetter(),
//                 'rate' => 10,
//                 'valid_from' => Carbon::now()->subMonth()
//             ]
//             );

//         $this->expectException(VatPeriodOverlap::class);
//         $this->expectExceptionMessage('A VAT record already exists for that period');

//         $vat->save();
//     }
}
