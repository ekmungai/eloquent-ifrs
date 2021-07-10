<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class SelfClearance extends IFRSException
{
    /**
     * Self Clearance Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Transaction cannot be used to clear itself ";

        parent::__construct($error . $message, $code);
    }
}
