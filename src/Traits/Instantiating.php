<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Traits;

use IFRS\Models\Transaction;
use IFRS\Transactions\AbstractTransaction;

/**
 *
 * @author emung
 *
 */
trait Instantiating
{
    /**
     * Instantiate IFRS Transaction of the given type.
     *
     * @param string $type
     *
     * @return AbstractTransaction
     */
    public static function instantiate(string $type) : AbstractTransaction
    {
        $transactionclass = 'IFRS\Transactions\\'.Transaction::$transactionClasses[$type];
        return new $transactionclass;
    }
}
