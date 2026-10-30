<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Traits;

use Carbon\Carbon;

use IFRS\Models\Assignment;

/**
 *
 * @author emung
 */
trait Clearing
{
    /**
     * Cleared Transaction amount.
     */
    public function getClearedAmountAttribute()
    {
        $cleared = 0;
        $this->load('clearances');

        foreach ($this->clearances as $clearance) {
            $cleared += $clearance->amount;
        }

        return $cleared;
    }

    /**
     * Cleared Transaction amount as at the end of the given date.
     *
     * @param Carbon $date
     *
     * @return float
     */
    public function clearedAmountAsAt(Carbon $date): float
    {
        $this->load('clearances');

        return $this->clearances->filter(function ($clearance) use ($date) {
            return Carbon::parse($clearance->assignment_date)->lte($date->copy()->endOfDay());
        })->sum('amount');
    }

    /**
     * Cleared Model records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function clearances()
    {
        return $this->morphMany(Assignment::class, 'cleared');
    }
}
