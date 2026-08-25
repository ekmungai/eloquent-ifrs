<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use IFRS\Models\Transaction;

class VatCharge extends IFRSException
{
    /**
     * Vat Charge Exception
     *
     * @param string $transactionType
     * @param string $message
     * @param int $code
     */
    public function __construct($transactionType, ?string $message = null, ?int $code = null)
    {
        $transactionType = Transaction::getType($transactionType);

        $error = $transactionType . " LineItems cannot be Charged VAT ";

        parent::__construct($error . $message, $code);
    }
}
