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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use IFRS\Models\Account;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;
use IFRS\Models\Currency;

/**
 *
 * @author emung
 */
trait Fetching
{
    /**
     * Fetch Transactions given the filters
     *
     * @param Carbon   $startTime
     * @param Carbon   $endTime
     * @param Account  $account
     * @param Currency $currency
     *
     * @return Collection
     */
    public static function fetch(
        Carbon $startTime = null,
        Carbon $endTime = null,
        Account $account = null,
        Currency $currency = null
    ) : Collection {
        $query = Transaction::where("transaction_type", self::PREFIX);

        // Account filter
        if (!is_null($account)) {
            $query->where("account_id", $account->id);
        }

        // startTime Filter
        if (!is_null($startTime)) {
            $query->where("date", ">=", $startTime);
        } else {
            $query->where("date", ">=", ReportingPeriod::periodStart());
        }

        // endTime Filter
        if (!is_null($endTime)) {
            $query->where("date", "<=", $endTime);
        }

        // Currency Filter
        if (!is_null($currency)) {
            $query->where("currency_id", $currency->id);
        } else {
            $entity = Auth::user()->entity;
            $query->where("currency_id", $entity->currency->id);
        }

        return $query->get();
    }
}
