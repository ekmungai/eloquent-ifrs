<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MixedAssignment extends IFRSException
{
    /**
     * Mixed Assignment Exception
     *
     * @param string $previous
     * @param string $current
     * @param string $message
     * @param int $code
     */
    public function __construct(string $previous, string $current, ?string $message = null, ?int $code = null)
    {
        $error = "A Transaction that has been " . $previous . " cannot be " . $current;

        parent::__construct($error . $message. ' ', $code);
    }
}
