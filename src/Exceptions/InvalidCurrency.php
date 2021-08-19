<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use IFRS\Models\Account;

class InvalidCurrency extends IFRSException
{
    /**
     * Invalid Currency Exception
     *
     * @param string $changeType
     * @param Account $account
     * @param string $message
     * @param int $code
     */
    public function __construct(string $changeType, Account $account, string $message = null, int $code = null)
    {
        $error = $changeType. " Currency must be the same as the ".$account->toString(true)." Account Currency ";

        parent::__construct($error . $message, $code);
    }
}
