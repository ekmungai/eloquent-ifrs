<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use IFRS\Models\Balance;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;
use IFRS\Scopes\EntityScope;

/**
 * Finds and moves Balances that were attached to the wrong Reporting Period.
 *
 * Before 7.0.0 current_reporting_period ignored year_start, so from January until
 * the month before year_start it returned the period labelled with the current
 * calendar year, which is that of the next financial year. Balance::save() defaults
 * reporting_period_id to that period, so a Balance saved in those months without an
 * explicit period landed a year late. Transactions were not affected, as they
 * resolve their period from their own date.
 *
 * That leaves a precise fingerprint: a Balance created before year_start whose
 * period is labelled with the calendar year it was created in. A Balance given that
 * period on purpose looks the same, so the findings need reviewing before they are
 * applied.
 */
class MisattachedBalances
{
    /**
     * Why a finding cannot be moved.
     *
     * @var string
     */
    const CLOSED_PERIOD = 'Reporting Period is closed';
    const MISSING_PERIOD = 'Reporting Period does not exist';

    /**
     * Find the Balances attached to the wrong Reporting Period.
     *
     * @param Entity $entity
     *
     * @return Collection
     */
    public static function find(?Entity $entity = null): Collection
    {
        $entities = is_null($entity) ? Entity::all() : collect([$entity]);

        return $entities->flatMap(function ($entity) {
            return self::findForEntity($entity);
        })->values();
    }

    /**
     * Move the Balances found to the Reporting Period they belong to, leaving those
     * that cannot be moved.
     *
     * @param Collection $findings
     *
     * @return int The number of Balances moved
     */
    public static function repair(Collection $findings): int
    {
        $repairable = $findings->whereNull('blocked');

        DB::transaction(function () use ($repairable) {
            foreach ($repairable as $finding) {
                $balance = $finding->balance;

                // The default transaction number carries the year of the period
                $default = $balance->account_id . $balance->currency->currency_code . $finding->from->calendar_year;

                // Query builder, as save() validates Balances being entered today
                DB::table($balance->getTable())->where('id', $balance->id)->update([
                    'reporting_period_id' => $finding->to->id,
                    'transaction_no' => $balance->transaction_no == $default
                        ? $balance->account_id . $balance->currency->currency_code . $finding->to->calendar_year
                        : $balance->transaction_no,
                ]);
            }
        });

        return $repairable->count();
    }

    /**
     * Find the Entity's Balances attached to the wrong Reporting Period.
     *
     * @param Entity $entity
     *
     * @return Collection
     */
    private static function findForEntity(Entity $entity): Collection
    {
        // A year starting in January has no months before it for the fault to occur in
        if ($entity->year_start == 1) {
            return collect();
        }

        $periods = ReportingPeriod::withoutGlobalScope(EntityScope::class)
            ->where('entity_id', $entity->id)->get()->keyBy('id');

        return Balance::withoutGlobalScope(EntityScope::class)
            ->where('entity_id', $entity->id)->whereNotNull('created_at')->get()
            ->filter(function ($balance) use ($entity, $periods) {
                $period = $periods->get($balance->reporting_period_id);

                return !is_null($period)
                    && $balance->created_at->month < $entity->year_start
                    && $period->calendar_year == $balance->created_at->year;
            })
            ->map(function ($balance) use ($periods) {
                $from = $periods->get($balance->reporting_period_id);
                $to = $periods->firstWhere('calendar_year', $from->calendar_year - 1);

                return (object) [
                    'balance' => $balance,
                    'from' => $from,
                    'to' => $to,
                    'blocked' => self::blocked($from, $to),
                ];
            })->values();
    }

    /**
     * Why a Balance cannot be moved between the Reporting Periods, if it cannot.
     *
     * @param ReportingPeriod $from
     * @param ReportingPeriod $to
     *
     * @return string|null
     */
    private static function blocked(ReportingPeriod $from, ?ReportingPeriod $to): ?string
    {
        if (is_null($to)) {
            return self::MISSING_PERIOD;
        }

        if ($from->status == ReportingPeriod::CLOSED || $to->status == ReportingPeriod::CLOSED) {
            return self::CLOSED_PERIOD;
        }

        return null;
    }
}
