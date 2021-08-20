<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class ClosedReportingPeriod extends IFRSException
{
    /**
     * Closed Reporting Period Exception
     *
     * @param int $year
     * @param string $message
     * @param int $code
     */
    public function __construct(int $year, string $message = null, int $code = null)
    {
        $error = "Transaction cannot be saved because the Reporting Period for " . $year . " is closed ";

        parent::__construct($error . $message, $code);
    }
}
