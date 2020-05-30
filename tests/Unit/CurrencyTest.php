<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;

class CurrencyTest extends TestCase
{
    /**
     * Currency Model relationships test.
     *
     * @return void
     */
    public function testCurrencyRelationships()
    {
        $currency = new Currency(
            [
            'name' => $this->faker->word,
            'currency_code' => $this->faker->currencyCode,
            ]
        );
        $currency->attributes();
        $currency->save();

        $exchangeRate = factory(ExchangeRate::class)->create(
            [
            'currency_id'=> $currency->id,
            ]
        );

        $this->assertEquals(
            $currency->exchangeRates->first()->rate,
            $exchangeRate->rate
        );
        $this->assertEquals(
            $currency->identifier(),
            'Currency: '.$currency->name.' ('.$currency->currency_code.')'
        );
    }

    /**
     * Test Currency Model recylcling
     *
     * @return void
     */
    public function testCurrencyRecycling()
    {
        $currency = new Currency(
            [
            'name' => $this->faker->word,
            'currency_code' => $this->faker->currencyCode,
            ]
        );
        $currency->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($currency->recycled->first(), $recycled);
    }
}
