<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\ClosingRate;
use IFRS\Models\Entity;
use IFRS\Models\ExchangeRate;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;

use IFRS\Exceptions\DuplicateClosingRate;
class ClosingRateTest extends TestCase
{
    /**
     * ClosingRate Model relationships test.
     *
     * @return void
     */
    public function testClosingRateRelationships()
    {
        $exchangeRate = factory(ExchangeRate::class)->create();
        $reportingPeriod = factory(ReportingPeriod::class)->create();

        $closingRate = ClosingRate::create([
            'exchange_rate_id' => $exchangeRate->id,
            'reporting_period_id' => $reportingPeriod->id,
        ]);

        $closingRate->attributes();

        $this->assertEquals($closingRate->exchangeRate->rate, $exchangeRate->rate);
        $this->assertEquals($closingRate->exchangeRate->currency, $exchangeRate->currency);
        $this->assertEquals($closingRate->reportingPeriod->calendar_year, $reportingPeriod->calendar_year);

        $this->assertEquals(
            $closingRate->toString(true),
            'ClosingRate: ' . $reportingPeriod->calendar_year . ' ' . $exchangeRate->currency->currency_code . ' at ' . $exchangeRate->rate
        );
        $this->assertEquals(
            $closingRate->toString(),
            $reportingPeriod->calendar_year . ' ' . $exchangeRate->currency->currency_code . ' at ' . $exchangeRate->rate
        );
    }

    /**
     * Test ClosingRate model Entity Scope.
     *
     * @return void
     */
    public function testClosingRateEntityScope()
    {
        $newEntity = factory(Entity::class)->create();

        $user = factory(User::class)->create();
        $user->entity()->associate($newEntity);
        $user->save();

        $this->be($user);

        ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'reporting_period_id' => factory(ReportingPeriod::class)->create()->id,
        ]);

        $this->assertEquals(count(ClosingRate::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ClosingRate::all()), 0);
    }

    /**
     * Test Closing Rate Model recylcling
     *
     * @return void
     */
    public function testClosingRateRecycling()
    {
        $closingRate = ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'reporting_period_id' => factory(ReportingPeriod::class)->create()->id,
        ]);

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($closingRate->recycled->first(), $recycled);
    }

    /**
     * Test Closing Rate Duplicate
     *
     * @return void
     */
    public function testClosingRateDuplicate()
    {
        $rate = factory(ExchangeRate::class)->create();
        $period = factory(ReportingPeriod::class)->create();

        ClosingRate::create([
            'exchange_rate_id' => $rate->id,
            'reporting_period_id' => $period->id,
        ]);

        ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'reporting_period_id' => $period->id,
        ]);
        ClosingRate::create([
            'exchange_rate_id' => $rate->id,
            'reporting_period_id' => factory(ReportingPeriod::class)->create()->id,
        ]);

        $this->expectException(DuplicateClosingRate::class);
        $this->expectExceptionMessage('A Closing Rate already exists for ' . $rate->currency->currency_code . ' for ' . $period->calendar_year);

        ClosingRate::create([
            'exchange_rate_id' => $rate->id,
            'reporting_period_id' => $period->id,
        ]);
    }
}
