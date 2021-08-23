<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MissingVatAccount extends IFRSException
{
    /**
     * Missing Vat Account Exception
     *
     * @param float $vatRate
     * @param string $message
     * @param int $code
     */
    public function __construct(float $vatRate, string $message = null, int $code = null)
    {
        $error = $vatRate . "% VAT requires a Vat Account ";

        parent::__construct($error . $message, $code);
    }
}
