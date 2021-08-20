<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class DuplicateClosingRate extends IFRSException
{
    /**
     * Duplicate Closing Rate Exception
     *
     * @param string $currencyCode
     * @param int $year
     * @param string $message
     * @param int $code
     */
    public function __construct(string $currencyCode, int $year, string $message = null, int $code = null)
    {
        $error = "A Closing Rate already exists for " . $currencyCode . " for " . $year;

        parent::__construct($error . $message, $code);
    }
}
