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
use Ekmungai\IFRS\Models\Account;

class LineItemAccount extends IFRSException
{
    /**
     * LineItem Account Exception
     *
     * @param string $transactionType
     * @param array $accountTypes
     * @param string $message
     * @param int $code
     */
    public function __construct(string $transactionType, array $accountTypes, string $message = null, int $code = null)
    {
        $transactionType = Transaction::getType($transactionType);
        $accountTypes = Account::getTypes($accountTypes);

        $error = $transactionType._(" LineItem Account must be of type ").implode(", ", $accountTypes);

        parent::__construct($error.' '.$message, $code);
    }
}
