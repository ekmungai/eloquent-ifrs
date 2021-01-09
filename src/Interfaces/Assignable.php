<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Interfaces;

/**
 *
 * @author emung
 */
interface Assignable
{
    /**
     * Balance Remaining on Transaction.
     *
     * @return float
     */
    public function getBalanceAttribute();
}
