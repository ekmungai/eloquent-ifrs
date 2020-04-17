<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\RecycledObject;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * Test User model Entity Scope.
     *
     * @return void
     */
    public function testUserEntityScope()
    {
        $users = factory(User::class, 5)->create()
            ->each(
                function ($user) {
                    $user->entity_id = 2;
                    $user->save();
                }
            );

        $this->assertEquals(count(User::all()), 1);

        $this->be($users[0]);

        $this->assertEquals(count(User::all()), 5);
    }

    /**
     * Test User Model recylcling
     *
     * @return void
     */
    public function testUserRecycling()
    {
        $user = factory(User::class)->create();
        $user->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($user->recycled->first(), $recycled);
    }
}
