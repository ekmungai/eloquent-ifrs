<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MissingClosingRate extends IFRSException
{
    /**
     * Missing Closing Rate Exception
     *
     * @param $currencyCode
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $currencyCode, string $message = null, int $code = null)
    {
        $error = "Closing Rate for " . $currencyCode . " is missing  ";

        parent::__construct($error . $message, $code);
    }
}
