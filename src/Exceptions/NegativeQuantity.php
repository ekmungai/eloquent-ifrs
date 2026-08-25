<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class NegativeQuantity extends IFRSException
{
    /**
     * Negative Quantity Exception
     *
     * @param string $modelType
     * @param string $message
     * @param int    $code
     */
    public function __construct(?string $message = null, ?int $code = null)
    {
        $error = "LineItem Quantity cannot be negative ";

        parent::__construct($error . $message, $code);
    }
}
