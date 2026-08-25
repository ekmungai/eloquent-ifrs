<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class VatPeriodOverlap extends IFRSException
{
    /**
     * Vat Period Overlap
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(?string $message = null, ?int $code = null)
    {
        $error = "A VAT record already exists for that period";

        parent::__construct($error . $message, $code);
    }
}
