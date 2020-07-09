<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Traits;

use IFRS\Models\Assignment;

trait Assigning
{
    /**
     * Balance Remaining on Transaction.
     */
    public function getBalanceAttribute()
    {
        $balance = 0;
        $this->load('assignments');
        foreach ($this->assignments as $assignment) {
            // print($this->assignments);
            $balance += $assignment->amount;
        }
        return $this->amount / $this->exchangeRate->rate - $balance;
    }
}
