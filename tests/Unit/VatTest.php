<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\RecycledObject;
use IFRS\Models\Vat;

use IFRS\Exceptions\InvalidAccountType;
use IFRS\Exceptions\MissingVatAccount;

class VatTest extends TestCase
{
    /**
     * Test Vat model Entity Scope.
     *
     * @return void
     */
    public function testVatEntityScope()
    {
        $newEntity = factory(Entity::class)->create();
        $newEntity->currency_id = factory(Currency::class)->create()->id;

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id,
        ]);
        $vat->attributes();
        $vat->save();

        $this->assertEquals(
            $vat->toString(true),
            'Vat: ' . $vat->name . ' (' . $vat->code . ') at ' . number_format($vat->rate, 2) . '%'
        );
        $this->assertEquals(
            $vat->toString(),
            $vat->name . ' (' . $vat->code . ') at ' . number_format($vat->rate, 2) . '%'
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
        $vat = Vat::create([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::CONTROL,
                'category_id' => null
            ])->id,
        ]);
        $vat->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($vat->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $vat->id);

        $vat->restore();

        $this->assertEquals(count($vat->recycled()->get()), 0);
        $this->assertEquals($vat->deleted_at, null);

        //'hard' delete
        $vat->forceDelete();

        $this->assertEquals(count(Vat::all()), 0);
        $this->assertEquals(count(Vat::withoutGlobalScopes()->get()), 1);
        $this->assertNotEquals($vat->deleted_at, null);
        $this->assertNotEquals($vat->destroyed_at, null);

        //destroyed objects cannot be restored
        $vat->restore();

        $this->assertNotEquals($vat->deleted_at, null);
        $this->assertNotEquals($vat->destroyed_at, null);
    }

    /**
     * Test Missing Vat Account.
     *
     * @return void
     */
    public function testMissingVatAccount()
    {
        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
        ]);
        $this->expectException(MissingVatAccount::class);
        $this->expectExceptionMessage($vat->rate . '% VAT requires a Vat Account');

        $vat->save();
    }

    /**
     * Test Invalid Vat Account Type.
     *
     * @return void
     */
    public function testInvalidVatAccountType()
    {
        $vat = new Vat([
            'name' => $this->faker->name,
            'code' => $this->faker->randomLetter(),
            'rate' => 10,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
        ]);
        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Vat Account must be of Type Control ');

        $vat->save();
    } 
}
