<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Interfaces;

/**
 *
 * @author emung
 *
 */
interface Assignable
{
    /**
     * Balance Remaining on Transaction.
     *
     * @return float
     */
    public function balance();

    /**
     * Get Transaction Id.
     *
     * @return int
     */
    public function getId();
}
