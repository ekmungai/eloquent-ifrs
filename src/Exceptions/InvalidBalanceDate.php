<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class InvalidBalanceDate extends IFRSException
{

    /**
     * Invalid Balance Date Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Transaction date must be earlier than the first day of the Balance's Reporting Period ";

        parent::__construct($error . ' ' . $message, $code);
    }
}
