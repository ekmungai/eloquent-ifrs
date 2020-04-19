<?php

namespace Tests\Unit;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\User;
use Carbon\Carbon;
use Ekmungai\IFRS\Models\Currency;

class ExchangeRateTest extends TestCase
{
    /**
     * ExchangeRate Model relationships test.
     *
     * @return void
     */
    public function testExchangeRateRelationships()
    {
        $currency = Currency::new($this->faker->word, $this->faker->currencyCode);
        $currency->save();

        $exchangeRate = ExchangeRate::new(
            Carbon::now(),
            Carbon::now()->addMonth(),
            $currency,
            10
        );
        $exchangeRate->attributes();
        $exchangeRate->save();

        $this->assertEquals($exchangeRate->currency->name, $currency->name);
    }

    /**
     * Test ExchangeRate model Entity Scope.
     *
     * @return void
     */
    public function testExchangeRateEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $exchangeRate = ExchangeRate::new(
            Carbon::now(),
            Carbon::now()->addMonth(),
            factory(Currency::class)->create(),
            10
        );
        $exchangeRate->save();

        $this->assertEquals(count(ExchangeRate::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ExchangeRate::all()), 0);
    }

    /**
     * Test ExchangeRate Model recylcling
     *
     * @return void
     */
    public function testExchangeRateRecycling()
    {
        $exchangeRate = ExchangeRate::new(
            Carbon::now(),
            Carbon::now()->addMonth(),
            factory(Currency::class)->create(),
            10
        );
        $exchangeRate->save();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($exchangeRate->recycled->first(), $recycled);
    }
}
