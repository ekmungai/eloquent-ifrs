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

use IFRS\Models\Assignment;
use IFRS\Models\Balance;
use IFRS\Models\Entity;
use IFRS\Scopes\EntityScope;

/**
 * Finds records that versions before 7.1.1 may have corrupted.
 *
 * Nothing here changes data. An over cleared Transaction is a fact, but how to put
 * it right is an accounting decision. A compounded Balance cannot be told apart from
 * one that was legitimately re-entered, so those findings are candidates to check
 * against the source of the Balance.
 */
class BalanceAudit
{
    /**
     * Foreign currency Balances saved after they were created, which versions before
     * 7.1.1 translated by their exchange rate again on each save.
     *
     * @param Entity $entity
     *
     * @return Collection
     */
    public static function compoundedCandidates(?Entity $entity = null): Collection
    {
        $query = Balance::withoutGlobalScope(EntityScope::class);

        if (!is_null($entity)) {
            $query->where('entity_id', $entity->id);
        }

        return $query->get()->filter(function ($balance) {
            return $balance->exchangeRate->rate != 1 && $balance->updated_at->gt($balance->created_at);
        })->values();
    }

    /**
     * Transactions and Balances cleared by more than their amount, which versions
     * before 7.1.1 allowed by amounts of less than 1.
     *
     * @param Entity $entity
     *
     * @return Collection
     */
    public static function overCleared(?Entity $entity = null): Collection
    {
        $query = Assignment::withoutGlobalScope(EntityScope::class);

        if (!is_null($entity)) {
            $query->where('entity_id', $entity->id);
        }

        return $query->get()->groupBy(function ($assignment) {
            return $assignment->cleared_type . $assignment->cleared_id;
        })->map(function ($assignments) {
            $first = $assignments->first();
            $cleared = $first->cleared;

            return (object) [
                'type' => class_basename($first->cleared_type),
                'id' => $first->cleared_id,
                'amount' => is_null($cleared) ? null : $cleared->amount,
                'cleared' => $assignments->sum('amount'),
            ];
        })->filter(function ($finding) {
            return !is_null($finding->amount) && round($finding->cleared, 4) > round($finding->amount, 4);
        })->values();
    }
}
