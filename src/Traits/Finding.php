<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Traits;

use IFRS\Models\Transaction;

/**
 *
 * @author emung
 */
trait Finding
{
    /**
     * Instantiate IFRS Transaction with Transaction model from the given Id.
     *
     * @param int $id
     *
     * @return Transaction
     */
    public static function find(int $id) : Transaction
    {
        return Transaction::find($id);
    }
}
