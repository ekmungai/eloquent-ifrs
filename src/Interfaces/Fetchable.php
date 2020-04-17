<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Interfaces;

use App\Models\Currency;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 *
 * @author emung
 *
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
    ) : Collection;
}
