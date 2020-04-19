<?php

namespace Tests\Unit;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\User;

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

        $this->assertEquals($users[0]->entity->id, 2);
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
        $user->attributes();
        $user->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($user->recycled->first(), $recycled);
    }
}
