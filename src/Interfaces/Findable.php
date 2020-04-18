<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Interfaces;

use Ekmungai\IFRS\Transactions\AbstractTransaction;

/**
 *
 * @author emung
 *
 */
interface Findable
{
    /**
     * Find Transaction by given Id.
     *
     * @param int $id
     *
     * @return AbstractTransaction
     */
    public static function find(int $id): AbstractTransaction;
}
