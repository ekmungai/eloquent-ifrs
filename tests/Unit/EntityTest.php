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
        // Parent entity
        $entity = new Entity([
            'name' => $this->faker->company,
        ]);
        $entity->attributes();
        $entity->save();

        $user = factory(User::class)->create();
        $user->entity()->associate($entity);
        $user->save();

        $this->be($user);

        $currency = factory(Currency::class)->create([
            'name' => 'Test Currency'
        ]);

        $entity->currency_id = $currency->id; // Reporting Currency must be explicitly set
        $entity->save();

        $period = factory(ReportingPeriod::class)->create([
            'entity_id' => $entity->id,
            'calendar_year' => date("Y")
        ]);

        $currency2 = factory(Currency::class)->create([
            'name' => 'Test Currency 2'
        ]);

        // Daughter entity
        $entity2 = new Entity([
            'name' => $this->faker->company,
            'currency_id' => $currency2->id,
            'parent_id' => $entity->id,
        ]);
        $entity2->save();

        // Second daughter entity
        $entity3 = new Entity([
            'name' => $this->faker->company,
            'currency_id' => $currency2->id,
            'parent_id' => $entity->id,
        ]);
        $entity3->save();

        $currency = Currency::find($currency->id);

        $this->assertEquals($user->entity->name, $entity->name);
        $this->assertEquals($entity->currency->name, $currency->name);
        $this->assertEquals($entity->currencies[1]->name, $currency2->name);
        $this->assertEquals($currency->entity->name, $entity->name);
        $this->assertEquals($entity->current_reporting_period->calendar_year, $period->calendar_year);
        $this->assertEquals($entity->toString(true), 'Entity: ' . $entity->name);
        $this->assertEquals($entity->toString(), $entity->name);
        $this->assertEquals($entity->default_rate->rate, 1);

        // Daughters
        $this->assertNull($entity->parent);
        $this->assertEquals($entity2->parent->name, $entity->name);
        $this->assertEquals($entity2->reportingCurrency->name, $currency->name); // daughters report in the parent's reporting currency
        $this->assertEquals($entity->daughters[0]->name, $entity2->name);
        $this->assertEquals($entity3->parent->name, $entity->name);
        $this->assertEquals($entity3->reportingCurrency->name, $currency->name); // daughters report in the parent's reporting currency
        $this->assertEquals($entity->daughters[1]->name, $entity3->name);
    }

    /**
     * Test Entity Model recylcling
     *
     * @return void
     */
    public function testEntityRecycling()
    {
        $entity = new Entity([
            'name' => $this->faker->company,
            'currency_id' => factory(Currency::class)->create()->id,
        ]);
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
