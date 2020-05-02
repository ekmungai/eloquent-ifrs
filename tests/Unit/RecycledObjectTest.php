<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\RecycledObject;
use IFRS\Models\User;

class RecycledObjectTest extends TestCase
{
    /**
     * RecycledObject Model relationships test.
     *
     * @return void
     */
    public function testRecycledObjectRelationships()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        factory(User::class)->create()->delete();

        $recycled = RecycledObject::all()->first();
        $recycled->attributes();
        $recycled->recyclable();
        $this->assertEquals($recycled->user->id, $user->id);
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
        $user = factory(User::class)->create();

        //soft delete
        $user->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($user->recycled->first(), $recycled);

        $user->restore();

        $this->assertEquals(count($user->recycled()->get()), 0);
        $this->assertEquals($user->deleted_at, null);

        //'hard' delete
        $user->forceDelete();

        $this->assertEquals(count(User::all()), 1);
        $this->assertEquals(count(User::withoutGlobalScopes()->get()), 2);
        $this->assertNotEquals($user->deleted_at, null);
        $this->assertNotEquals($user->destroyed_at, null);
    }
}
