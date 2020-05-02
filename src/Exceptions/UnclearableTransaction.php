<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Exceptions;

use IFRS\Models\Transaction;

class UnclearableTransaction extends IFRSException
{
    /**
     * Unclearable Transaction Exception
     *
     * @param string $transactionType
     * @param array $transactionTypes
     * @param string $message
     * @param int $code
     */
    public function __construct(string $transactionType, array $transactionTypes, string $message = null, int $code = null)
    {
        $transactionTypes = Transaction::getTypes($transactionTypes);
        $transactionType = Transaction::getType($transactionType);

        $error = $transactionType._(" Transaction cannot be cleared. Transaction to be cleared must be one of: ");
        $error .= implode(", ", $transactionTypes).' ';

        parent::__construct($error.$message, $code);
    }
}
