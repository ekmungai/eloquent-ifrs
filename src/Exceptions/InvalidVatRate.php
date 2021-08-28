<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class InvalidVatRate extends IFRSException
{
    /**
     * Invalid Compound Journal Entry Vat Rate Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Compound Journal Entry Vat objects must all be null or zero rated ";

        parent::__construct($error . $message, $code);
    }
}
