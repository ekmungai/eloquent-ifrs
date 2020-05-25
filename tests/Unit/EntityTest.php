<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use Illuminate\Support\Facades\Auth;

use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\User;
use IFRS\Models\Account;

use IFRS\Exceptions\UnauthorizedUser;

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

        $entity = new Entity(
            [
            'name' => $this->faker->company,
            'currency_id' => $currency->id,
            ]
        );
        $entity->attributes();
        $entity->save();

        $user = factory(User::class)->create();
        $user->entity_id = $entity->id;
        $user->save();

        $this->be($user);

        $period = factory(ReportingPeriod::class)->create();
        $period->entity_id = $entity->id;
        $period->save();

        $this->assertEquals($user->entity->name, $entity->name);
        $this->assertEquals($entity->currency->name, $currency->name);
        $this->assertEquals($entity->reportingPeriods[0]->year, $period->year);
    }

    /**
     * Test Entity Model recylcling
     *
     * @return void
     */
    public function testEntityRecycling()
    {
        $entity = new Entity(
            [
            'name' => $this->faker->company,
            'currency_id' => factory(Currency::class)->create()->id,
            ]
        );
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
