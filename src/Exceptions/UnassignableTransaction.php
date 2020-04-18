<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

use Ekmungai\IFRS\Models\Transaction;

/**
 *
 * @author emung
 *
 */
class UnassignableTransaction extends IFRSException
{
    /**
     * Unassignable Transaction Exception
     *
     * @param string $transactionType
     * @param array $transactionTypes
     * @param string $message
     * @param int $code
     */
    public function __construct(string $transactionType, array $transactionTypes, string $message = null, int $code = 0)
    {
        $transactionTypes = Transaction::getTypes($transactionTypes);
        $transactionType = Transaction::getType($transactionType);

        $error = $transactionType._(" Transaction cannot have assignments. Assignment Transaction must be one of: ");
        $error .= implode(", ", $transactionTypes).' ';

        parent::__construct($error.$message, $code);
    }
}
