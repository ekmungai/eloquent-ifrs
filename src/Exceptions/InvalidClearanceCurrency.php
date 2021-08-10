<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class InvalidClearanceCurrency extends IFRSException
{
    /**
     * Invalid Clearance Currency Exception
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Assignment and Clearance Currency must be the same ";

        parent::__construct($error . $message, $code);
    }
}
