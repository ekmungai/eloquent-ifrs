<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use IFRS\Models\ReportingPeriod;

class InvalidPeriodStatus extends IFRSException
{
    /**
     * Invalid Period Status Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Reporting Period must have " . config('ifrs')['reporting_period_status'][ReportingPeriod::ADJUSTING] . " status to translate foreign balances";

        parent::__construct($error . $message, $code);
    }
}
