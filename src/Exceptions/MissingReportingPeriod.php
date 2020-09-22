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

class MissingReportingPeriod extends IFRSException
{
    /**
     * Missing Reporting Period Exception
     *
     * @param string $entity
     * @param int    $year
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $entity, int $year, string $message = null, int $code = null)
    {
        $error = "Entity '" . $entity . "' has no reporting period defined for the year " . $year . " ";

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
