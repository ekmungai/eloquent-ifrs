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

class NegativeAmount extends IFRSException
{
    /**
     * Negative Amount Exception
     *
     * @param string $modelType
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $modelType, string $message = null, int $code = null)
    {
        $error = $modelType._(" Amount cannot be negative ");

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
