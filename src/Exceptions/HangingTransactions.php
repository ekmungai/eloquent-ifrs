<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT See LICENSE.md
 */

namespace IFRS\Exceptions;

class HangingTransactions extends IFRSException
{
    /**
     * Hanging Transactions Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Account cannot be deleted because it has existing Transactions/Balances in the current Reporting Period ";

        parent::__construct($error . $message, $code);
    }
}
