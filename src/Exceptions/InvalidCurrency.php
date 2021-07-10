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
     * @param string $accountType
     * @param string $message
     * @param int $code
     */
    public function __construct(string $changeType, string $accountType, string $message = null, int $code = null)
    {
        $accountType = Account::getType($accountType);
        $error = $changeType . " Currency must be the same as the " . $accountType . " Account Currency ";

        parent::__construct($error . $message, $code);
    }
}
