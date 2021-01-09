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

class InvalidBalanceTransaction extends IFRSException
{

    /**
     * Invalid Balance Transaction Exception
     *
     * @param array  $transactionTypes
     * @param string $message
     * @param int    $code
     */
    public function __construct(array $transactionTypes, string $message = null, int $code = null)
    {
        $transactionTypes = Transaction::getTypes($transactionTypes);

        $error = "Opening Balance Transaction must be one of: " . implode(", ", $transactionTypes);

        Log::notice(
            $error . $message,
            [
                'user_id' => Auth::user()->id,
                'time' => Carbon::now(),
            ]
        );

        parent::__construct($error . ' ' . $message, $code);
    }
}
