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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MixedAssignment extends IFRSException
{
    /**
     * Mixed Assignment Exception
     *
     * @param string $previous
     * @param string $current
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $previous, string $current, string $message = null, int $code = null)
    {
        $error = "A Transaction that has been " . $previous . " cannot be " . $current;

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
