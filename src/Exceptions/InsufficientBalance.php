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

use IFRS\Models\Transaction;

class InsufficientBalance extends IFRSException
{

    /**
     * Insufficient Balance Exception
     *
     * @param string $transactionType
     * @param float  $amount
     * @param string $assignedType
     * @param string $message
     * @param int    $code
     */
    public function __construct(
        string $transactionType,
        float $amount,
        string $assignedType,
        string $message = null,
        int $code = 0
    ) {
        $transactionType = Transaction::getType($transactionType);
        $assignedType = Transaction::getType($assignedType);

        $error = $transactionType . " Transaction does not have sufficient balance to clear ";
        $error .= $amount . ' of the ' . $assignedType;

        Log::notice(
            $error . ' ' . $message,
            [
                'user_id' => Auth::user()->id,
                'time' => Carbon::now(),
            ]
        );
        parent::__construct($error . ' ' . $message, $code);
    }
}
