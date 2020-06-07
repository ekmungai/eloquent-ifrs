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

class InvalidBalanceDate extends IFRSException
{

    /**
    * Invalid Balance Date Exception
    *
    * @param string $message
    * @param int    $code
    */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "Opening Balance Transaction date must be earlier than the first day of the Balance Reporting Period ";

        Log::notice(
            $error.$message,
            [
                'user_id' => Auth::user()->id,
                'time' => Carbon::now(),
            ]
            );

        parent::__construct($error.' '.$message, $code);
    }
}

