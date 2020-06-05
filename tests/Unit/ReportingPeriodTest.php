<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\User;

use IFRS\Exceptions\MissingReportingPeriod;

class ReportingPeriodTest extends TestCase
{
    /**
     * ReportingPeriod Model relationships test.
     *
     * @return void
     */
    public function testReportingPeriodRelationships()
    {
        $entity = Auth::user()->entity;

        $period = new ReportingPeriod(
            [
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
            ]
        );
        $period->save();

        $period->attributes();
        $this->assertEquals($entity->reportingPeriods->last()->calendar_year, $period->calendar_year);
        $this->assertEquals(
            $period->toString(true),
            'Reportiting Period: '.$period->calendar_year
        );
        $this->assertEquals(
            $period->toString(),
            $period->calendar_year
        );
    }

    /**
     * Test ReportingPeriod model Entity Scope.
     *
     * @return void
     */
    public function testReportingPeriodEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $this->assertEquals(count(ReportingPeriod::all()), 0);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ReportingPeriod::all()), 1);
    }

    /**
     * Test ReportingPeriod Model recylcling
     *
     * @return void
     */
    public function testReportingPeriodRecycling()
    {
        $period = new ReportingPeriod(
            [
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
            ]
        );

        $period->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($period->recycled->first(), $recycled);
    }

    /**
     * Test ReportingPeriod Dates
     *
     * @return void
     */
    public function testReportingPeriodDates()
    {
        $this->assertEquals(ReportingPeriod::year(), date("Y"));
        $this->assertEquals(ReportingPeriod::year("2025-06-25"), "2025");
        $this->assertEquals(ReportingPeriod::periodStart("2025-06-25")->toDateString(), "2025-01-01");

        $entity = Auth::user()->entity;
        $entity->year_start = 4;
        $entity->save();

        $this->assertEquals(ReportingPeriod::year("2025-03-25"), "2024");
        $this->assertEquals(ReportingPeriod::periodStart("2025-03-25")->toDateString(), "2024-04-01");
        $this->assertEquals(ReportingPeriod::periodEnd("2025-03-25")->toDateString(), "2025-03-31");
    }

    /**
     * Test Missing Report Period.
     *
     * @return void
     */
    public function testMissingReportPeriod()
    {
        $this->expectException(MissingReportingPeriod::class);
        $this->expectExceptionMessage('has no reporting period defined for the year');

        ReportingPeriod::getPeriod("1970");
    }
}
