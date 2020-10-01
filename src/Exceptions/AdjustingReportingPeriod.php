<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use IFRS\Models\Transaction;
use IFRS\Models\ReportingPeriod;

class AdjustingReportingPeriod extends IFRSException
{
    /**
     * Adjusting Reporting Period Exception
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $type = Transaction::getType(Transaction::JN);
        $error = "Only " . $type . " Transactions can be posted to a reporting period whose status is " . ReportingPeriod::ADJUSTING;

        Log::notice(
            $error . $message,
            [
                'user_id' => Auth::user()->id,
                'time' => Carbon::now(),
            ]
        );
        parent::__construct($error . $message, $code);
    }
}
