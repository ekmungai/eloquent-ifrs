<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use IFRS\Models\Account;
use IFRS\Models\Transaction;

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

        $error = $transactionType . " LineItem Account must be of type " . implode(", ", $accountTypes);

        parent::__construct($error . ' ' . $message, $code);
    }
}
