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

class MissingAccount extends IFRSException
{
    /**
     * Missing Account Exception
     *
     * @param $statementType
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $statementType, string $message = null, int $code = null)
    {
        $error = $statementType . " Transactions require an Account ";

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
