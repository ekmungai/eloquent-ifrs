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

class MissingVatAccount extends IFRSException
{
    /**
     * Missing Vat Account Exception
     *
     * @param float  $vatRate
     * @param string $message
     * @param int    $code
     */
    public function __construct(float $vatRate, string $message = null, int $code = null)
    {
        $error = $vatRate . "% VAT requires a Vat Account";

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
