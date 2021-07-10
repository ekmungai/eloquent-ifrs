<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Interfaces;

use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use Illuminate\Support\Collection;

/**
 *
 * @author emung
 */
interface Fetchable
{
    /**
     * Fetch Transaction by given filters.
     *
     * @return Collection
     */
    public static function fetch(
        Carbon $startTime = null,
        Carbon $endTime = null,
        Account $account = null,
        Currency $currency = null
    ): Collection;
}
