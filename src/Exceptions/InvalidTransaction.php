<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class InvalidTransaction extends IFRSException
{
    /**
     * Invalid Assignment Transaction Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Compound Journal Entry Transactions can be neither Assigned nor Cleared ";

        parent::__construct($error . $message, $code);
    }
}
