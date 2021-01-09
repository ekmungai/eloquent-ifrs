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

class PostedTransaction extends IFRSException
{
    /**
     * Posted Transaction Exception
     *
     * @param string $action
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $action, string $message = null, int $code = null)
    {
        $error = "Cannot " . $action . " a posted Transaction ";

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
