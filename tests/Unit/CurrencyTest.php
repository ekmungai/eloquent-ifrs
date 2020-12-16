<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\User;
use Illuminate\Support\Facades\Auth;

class CurrencyTest extends TestCase
{
    /**
     * Currency Model relationships test.
     *
     * @return void
     */
    public function testCurrencyRelationships()
    {
        $currency = new Currency([
            'name' => $this->faker->word,
            'currency_code' => $this->faker->currencyCode,
        ]);
        $currency->attributes();
        $currency->save();

        $entity = Auth::user()->entity;

        $exchangeRate = factory(ExchangeRate::class)->create([
            'currency_id' => $currency->id,
        ]);

        $this->assertEquals(
            $currency->exchangeRates->first()->rate,
            $exchangeRate->rate
        );

        $this->assertEquals(
            $currency->entity->name,
            $entity->name
        );
        $this->assertEquals(
            $currency->toString(true),
            'Currency: ' . $currency->name . ' (' . $currency->currency_code . ')'
        );
        $this->assertEquals(
            $currency->toString(),
            $currency->name . ' (' . $currency->currency_code . ')'
        );
    }

    /**
     * Test Currency model Entity Scope.
     *
     * @return void
     */
    public function testCurrencyEntityScope()
    {
        $newEntity = factory(Entity::class)->create();

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        factory(Currency::class, 3)->create();

        $this->assertEquals(count(Currency::all()), 3);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Currency::all()), 1); // Default Entity Reporting Currency
    }


    /**
     * Test Currency Model recylcling
     *
     * @return void
     */
    public function testCurrencyRecycling()
    {
        $currency = new Currency([
            'name' => $this->faker->word,
            'currency_code' => $this->faker->currencyCode,
        ]);
        $currency->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($currency->recycled->first(), $recycled);
    }
}
