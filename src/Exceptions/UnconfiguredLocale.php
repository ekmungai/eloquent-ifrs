<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class UnconfiguredLocale extends IFRSException
{
    /**
     * Missing Account Exception
     *
     * @param $locale
     * @param string $message
     * @param int $code
     */
    public function __construct(string $locale, ?string $message = null, ?int $code = null)
    {
        $error = "Locale " . $locale . " is not configured";

        parent::__construct($error . $message, $code);
    }
}
