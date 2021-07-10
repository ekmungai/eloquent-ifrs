<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class InvalidTransactionDate extends IFRSException
{

    /**
     * Invalid Transaction Date Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Transaction date cannot be at the beginning of the first day of the Reporting Period. Use a Balance object instead ";

        parent::__construct($error . ' ' . $message, $code);
    }
}
