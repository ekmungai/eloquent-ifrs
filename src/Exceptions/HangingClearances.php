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

class HangingClearances extends IFRSException
{
    /**
     * Hanging Clearances Exception
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = _("Transaction cannot be deleted because it has been used to to Clear other Transactions ");

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
