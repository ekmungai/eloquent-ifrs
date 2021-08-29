<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MissingMainAccountAmount extends IFRSException
{
    /**
     * Compound Journal Entry Transaction Missing Main Account Amount Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Compund Journal Entries must have a Main Account Amount ";

        parent::__construct($error . $message, $code);
    }
}
