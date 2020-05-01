<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Traits;

use Ekmungai\IFRS\Models\Transaction;
use Ekmungai\IFRS\Transactions\AbstractTransaction;

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
        $transactionclass = 'Ekmungai\IFRS\Transactions\\'.Transaction::$transactionClasses[$type];
        return new $transactionclass;
    }
}
