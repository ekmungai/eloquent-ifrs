<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class UnbalancedTransaction extends IFRSException
{
    /**
     * Unbalanced Compound Journal Entry Transaction Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(?string $message = null, ?int $code = null)
    {
        $error = "Total Debit amounts do not match total Credit amounts ";

        parent::__construct($error . $message, $code);
    }
}
