<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\User;
use Carbon\Carbon;
use IFRS\Models\Currency;

class ExchangeRateTest extends TestCase
{
    /**
     * ExchangeRate Model relationships test.
     *
     * @return void
     */
    public function testExchangeRateRelationships()
    {
        $currency  = factory(Currency::class)->create();

        $exchangeRate = new ExchangeRate(
            [
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => $currency->id,
            'rate' => 10
            ]
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

        $exchangeRate = new ExchangeRate(
            [
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => factory(Currency::class)->create()->id,
            'rate' => 10
            ]
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
        $exchangeRate = new ExchangeRate(
            [
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => factory(Currency::class)->create()->id,
            'rate' => 10
            ]
        );
        $exchangeRate->save();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($exchangeRate->recycled->first(), $recycled);
    }
}
