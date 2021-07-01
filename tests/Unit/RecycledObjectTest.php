<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Auth;

use IFRS\User;
use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\RecycledObject;

class RecycledObjectTest extends TestCase
{
    /**
     * RecycledObject Model relationships test.
     *
     * @return void
     */
    public function testRecycledObjectRelationships()
    {
        factory(Currency::class)->create()->delete();

        $recycled = RecycledObject::all()->first();
        $recycled->attributes();
        $recycled->recyclable();

        $this->assertEquals($recycled->user->id, Auth::user()->id);
    }

    /**
     * Test RecycledObject model Entity Scope.
     *
     * @return void
     */
    public function testRecycledObjectEntityScope()
    {
        factory(RecycledObject::class, 3)->create()
            ->each(
                function ($period) {
                    $period->entity_id = 2;
                    $period->save();
                }
            );

        $this->assertEquals(count(RecycledObject::all()), 0);

        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $this->assertEquals(count(RecycledObject::all()), 3);
    }

    /**
     * RecycledObject test
     *
     * @return void
     */
    public function testObjectRecycling()
    {
        $account = factory(Account::class)->create([
            'category_id' => null
        ]);

        //soft delete
        $account->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($account->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $account->id);

        $account->restore();

        $this->assertEquals(count($account->recycled()->get()), 0);
        $this->assertEquals($account->deleted_at, null);

        //'hard' delete
        $account->forceDelete();

        $this->assertEquals(count(Account::all()), 0);
        $this->assertEquals(count(Account::withoutGlobalScopes()->get()), 1);
        $this->assertNotEquals($account->deleted_at, null);
        $this->assertNotEquals($account->destroyed_at, null);

        //destroyed objects cannot be restored
        $account->restore();

        $this->assertNotEquals($account->deleted_at, null);
        $this->assertNotEquals($account->destroyed_at, null);
    }
}
