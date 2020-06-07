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

use IFRS\Models\Balance;

class InvalidBalanceType extends IFRSException
{

    /**
     * Invalid Balance Type Exception
     *
     * @param array  $balanceTypes
     * @param string $message
     * @param int    $code
     */
    public function __construct(array $balanceTypes, string $message = null, int $code = null)
    {
        $balanceTypes = Balance::getTypes($balanceTypes);

        $error = "Opening Balance Type must be one of: ".implode(", ", $balanceTypes);

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
