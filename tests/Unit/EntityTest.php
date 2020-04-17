<?php

namespace Tests\Unit;

use Tests\TestCase;

use Illuminate\Support\Facades\Auth;

use App\Models\Currency;
use App\Models\Entity;
use App\Models\RecycledObject;
use App\Models\User;
use App\Models\Account;

use App\Exceptions\UnauthorizedUser;

class EntityTest extends TestCase
{
    /**
     * Entity Model relationships test.
     *
     * @return void
     */
    public function testEntityRelationships()
    {
        $currency = factory(Currency::class)->create();

        $entity = Entity::new($this->faker->company, $currency);
        $entity->save();

        $user = factory(User::class)->create();
        $user->entity_id = $entity->id;
        $user->save();

        $this->assertEquals($user->entity->name, $entity->name);
        $this->assertEquals($entity->currency->name, $currency->name);
    }

    /**
     * Test Entity Model recylcling
     *
     * @return void
     */
    public function testEntityRecycling()
    {
        $entity = Entity::new($this->faker->company, factory(Currency::class)->create());
        $entity->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($entity->recycled()->first(), $recycled);
    }

    /**
     * Test Entity Authorized User
     *
     * @return void
     */
    public function testEntityAuthorizedUser()
    {
        Auth::logout();

        $this->expectException(UnauthorizedUser::class);
        $this->expectExceptionMessage('You are not Authorized to perform that action');

        factory(Account::class)->create();
    }
}
