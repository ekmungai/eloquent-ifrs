<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Transaction;

/**
 *
 * @author emung
 *
 */
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
    public function __construct(string $transactionType, string  $accountType, string $message = null, int $code = 0)
    {
        $transactionType = Transaction::getType($transactionType);
        $accountType = Account::getType($accountType);

        parent::__construct($transactionType._(" Main Account must be of type ").$accountType.' '.$message, $code);
    }
}
