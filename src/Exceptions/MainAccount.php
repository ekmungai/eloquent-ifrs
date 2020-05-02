<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Exceptions;

use IFRS\Models\Account;
use IFRS\Models\Transaction;

class MainAccount extends IFRSException
{
    /**
     * Main Account Exception
     *
     * @param string $transactionType
     * @param string $accountType
     * @param string $message
     * @param int $code
     */
    public function __construct(string $transactionType, string  $accountType, string $message = null, int $code = null)
    {
        $transactionType = Transaction::getType($transactionType);
        $accountType = Account::getType($accountType);

        parent::__construct($transactionType._(" Main Account must be of type ").$accountType.' '.$message, $code);
    }
}
