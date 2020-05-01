<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Traits;

/**
 *
 * @author emung
 *
 */

trait Assigning
{
    /**
     * Balance Remaining on Transaction.
     */
    public function balance()
    {
        $balance = 0;
        foreach ($this->assignments as $assignment) {
            $balance += $assignment->amount;
        }
        return $this->amount/$this->exchangeRate->rate - $balance;
    }
}
