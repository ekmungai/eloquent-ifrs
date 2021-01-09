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

use IFRS\Models\Account;
use IFRS\Models\Transaction;

class MainAccount extends IFRSException
{
    /**
     * Main Account Exception
     *
     * @param string $transactionType
     * @param string $accountType
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $transactionType, string  $accountType, string $message = null, int $code = null)
    {
        $transactionType = Transaction::getType($transactionType);
        $accountType = Account::getType($accountType);

        $error = $transactionType . " Main Account must be of type " . $accountType;

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
