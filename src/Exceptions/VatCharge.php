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

class VatCharge extends IFRSException
{
    /**
     * Vat Charge Exception
     *
     * @param string $transactionType
     * @param string $message
     * @param int    $code
     */
    public function __construct($transactionType, string $message = null, int $code = null)
    {
        $transactionType = Transaction::getType($transactionType);

        $error = $transactionType._(" LineItems cannot be Charged VAT ");

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
