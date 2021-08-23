<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MultipleVatError extends IFRSException
{
    /**
     * Multiple Vat Error Exception
     * @param string $error
     * @param string $message
     * @param int $code
     */
    public function __construct(string $error, string $message = null, int $code = null)
    {
        parent::__construct($error . ' ' . $message, $code);
    }
}
