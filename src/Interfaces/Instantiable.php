<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Interfaces;

use App\Transactions\AbstractTransaction;

/**
 *
 * @author emung
 *
 */
interface Instantiable
{
    /**
     * Instantiate Transaction of the given type.
     *
     * @param string $type
     *
     * @return AbstractTransaction
     */
    public static function instantiate(string $type) : AbstractTransaction;
}
