<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Account;

use IFRS\Exceptions\UnauthorizedUser;
use IFRS\Exceptions\UnconfiguredLocale;
use IFRS\Exceptions\MissingReportingCurrency;

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
        $entity = Entity::create([
            'name' => $this->faker->company,
            'currency_id' => factory(Currency::class)->create()->id,
        ]);

        $entity->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($entity->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $entity->id);

        $entity->restore();

        $this->assertEquals(count($entity->recycled()->get()), 0);
        $this->assertEquals($entity->deleted_at, null);

        //'hard' delete
        $entity->forceDelete();

        $this->assertEquals(count(Entity::all()), 1);
        $this->assertEquals(count(Entity::withoutGlobalScopes()->get()), 2);
        $this->assertNotEquals($entity->deleted_at, null);
        $this->assertNotEquals($entity->destroyed_at, null);

        //destroyed objects cannot be restored
        $entity->restore();

        $this->assertNotEquals($entity->deleted_at, null);
        $this->assertNotEquals($entity->destroyed_at, null);
    }

    /**
     * Test Entity Authorized User
     *
     * @return void
     */
    public function estEntityAuthorizedUser()
    {
        Auth::logout();

        $this->expectException(UnauthorizedUser::class);
        $this->expectExceptionMessage('You are not Authorized to perform that action');

        factory(Account::class)->create();
    }

    /**
     * Test Entity Locale
     *
     * @return void
     */
    public function testEntityLocale()
    {
        $entity = new Entity([
            'name' => $this->faker->company,
        ]);
        $entity->save();

        $this->assertEquals($entity->locale, 'en_GB');

        $entity = new Entity([
            'name' => $this->faker->company,
            'locale' => 'ar_BH'
        ]);
        $entity->save();

        $this->assertEquals($entity->locale, 'ar_BH');
    }

    /**
     * Test Entity Locale Exception
     *
     * @return void
     */
    public function testEntityLocaleException()
    {
        $entity = new Entity([
            'name' => $this->faker->company,
            'locale' => 'en_US'
        ]);
        $this->expectException(UnconfiguredLocale::class);
        $this->expectExceptionMessage('Locale en_US is not configured');

        $entity->save();
    }

    /**
     * Test Entity Amount Localization
     *
     * @return void
     */
    public function testEntityAmountLocalization()
    {
        $entity = Auth::user()->entity;

        $currency = factory(Currency::class)->create([
            'name' => 'Euros',
            'currency_code' => 'EUR',
            'entity_id' => $entity->id
        ]);

        $entity->currency()->associate($currency);

        $this->assertEquals($entity->localizeAmount(1234567.891), "€1,234,567.89");
        $this->assertEquals($entity->localizeAmount(1234567.891, 'EUR', 'de_DE'), "1.234.567,89\xc2\xa0€");

        $entity = new Entity([
            'name' => $this->faker->company,
            'locale' => 'ar_BH'
        ]);
        $entity->save();

        $user = factory(User::class)->create();
        $user->entity()->associate($entity);
        $user->save();

        $this->be($user);

        // $this->assertEquals($entity->localizeAmount(1234567.891, 'EUR'), "١٬٢٣٤٬٥٦٧٫٨٩\xc2\xa0€");
        // $this->assertEquals($entity->localizeAmount(1234567.891, 'BHD'), "١٬٢٣٤٬٥٦٧٫٨٩١\xc2\xa0د.ب.‏");
    }

    /**
     * Test Entity Reporting Currency Exception
     *
     * @return void
     */
    public function testEntityReportingCurrencyException()
    {
        $entity = new Entity([
            'name' => $this->faker->company,
        ]);
        $entity->save();
        $this->expectException(MissingReportingCurrency::class);
        $this->expectExceptionMessage("Entity '" . $entity->name . "' has no Reporting Currency defined ");

        $entity->reportingCurrency;
    }
}
