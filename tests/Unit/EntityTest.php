<?php

namespace Tests\Unit;

use IFRS\Exceptions\DuplicateAssignment;
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
        $currency = factory(Currency::class)->create([
            'name' => 'Test Currency'
        ]);

        // Parent entity
        $entity = new Entity([
            'name' => $this->faker->company,
            'currency_id' => $currency->id,
        ]);
        $entity->attributes();
        $entity->save();

        $currency = Currency::find($currency->id);

        $user = factory(User::class)->create();
        $user->entity_id = $entity->id;
        $user->save();

        $this->be($user);

        $period = factory(ReportingPeriod::class)->create([
            'entity_id' => $entity->id,
            'calendar_year' => date("Y")
        ]);

        $currency2 = factory(Currency::class)->create([
            'entity_id' => $entity->id,
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
        $this->assertEquals($entity2->currency->name, $currency2->name);
        $this->assertEquals($entity->daughters[0]->name, $entity2->name);
        $this->assertEquals($entity3->parent->name, $entity->name);
        $this->assertEquals($entity3->currency->name, $currency2->name);
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

    /**
     * Test Currency Duplicate Assignment.
     *
     * @return void
     */
    public function testCurrencyDuplicateAssignment()
    {
        $currency = factory(Currency::class)->create();

        Entity::create([
            'name' => $this->faker->company,
            'currency_id' => $currency->id,
        ]);

        $entity = new Entity([
            'name' => $this->faker->company,
            'currency_id' => $currency->id,
        ]);

        $this->expectException(DuplicateAssignment::class);
        $this->expectExceptionMessage('This Currency has already been assigned to an Entity');

        $entity->save();
    }
}
