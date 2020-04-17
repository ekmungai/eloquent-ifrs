<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Traits;

use App\Models\Transaction;
use App\Transactions\AbstractTransaction;

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
        $transactionclass = 'App\Transactions\\'.Transaction::$transactionClasses[$type];
        return new $transactionclass;
    }
}
