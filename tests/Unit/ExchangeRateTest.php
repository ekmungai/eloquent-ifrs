<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
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

        $exchangeRate = new ExchangeRate([
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => $currency->id,
            'rate' => 10
        ]);
        $exchangeRate->attributes();
        $exchangeRate->save();

        $this->assertEquals($exchangeRate->currency->name, $currency->name);
        $this->assertEquals(
            $exchangeRate->toString(true),
            'ExchangeRate: ' . number_format($exchangeRate->rate, 2) . ' for ' . $exchangeRate->currency->toString() . ' from ' . $exchangeRate->valid_from->toDateString()
        );
        $this->assertEquals(
            $exchangeRate->toString(),
            number_format($exchangeRate->rate, 2) . ' for ' . $exchangeRate->currency->toString() . ' from ' . $exchangeRate->valid_from->toDateString()
        );
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

        $exchangeRate = new ExchangeRate([
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => factory(Currency::class)->create()->id,
            'rate' => 10
        ]);
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
        $exchangeRate = ExchangeRate::create([
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addMonth(),
            'currency_id' => factory(Currency::class)->create()->id,
            'rate' => 10
        ]);
        $exchangeRate->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($exchangeRate->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $exchangeRate->id);

        $exchangeRate->restore();

        $this->assertEquals(count($exchangeRate->recycled()->get()), 0);
        $this->assertEquals($exchangeRate->deleted_at, null);

        //'hard' delete
        $exchangeRate->forceDelete();

        $this->assertEquals(count(ExchangeRate::all()), 0);
        $this->assertEquals(count(ExchangeRate::withoutGlobalScopes()->get()), 1);
        $this->assertNotEquals($exchangeRate->deleted_at, null);
        $this->assertNotEquals($exchangeRate->destroyed_at, null);

        //destroyed objects cannot be restored
        $exchangeRate->restore();

        $this->assertNotEquals($exchangeRate->deleted_at, null);
        $this->assertNotEquals($exchangeRate->destroyed_at, null);
    }
}
