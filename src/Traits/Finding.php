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
trait Finding
{
    /**
     * Instantiate IFRS Transaction with Transaction model from the given Id.
     *
     * @param int $id
     *
     * @return AbstractTransaction
     */
    public static function find(int $id) : AbstractTransaction
    {
        $model = Transaction::find($id);

        $item = AbstractTransaction::instantiate($model->transaction_type);
        $item->existingTransaction($model);

        return $item;
    }
}
