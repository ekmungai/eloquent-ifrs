<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT See LICENSE.md
 */
namespace IFRS\Exceptions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class InvalidBalanceDate extends IFRSException
{
    /**
     * Invalid Balance Date
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Balance date must be earlier than the first day of the Balance's Reporting Period ";

        Log::notice(
            $error.$message,
            [
                'user_id' => Auth::user()->id,
                'time' => Carbon::now(),
            ]
            );

        parent::__construct($error.$message, $code);
    }
}

