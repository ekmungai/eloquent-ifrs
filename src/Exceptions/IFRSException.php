<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

namespace IFRS\Exceptions;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

abstract class IFRSException extends \Exception
{
    /**
     * Exception code
     *
     * @var int
     */
    public $code;

    /**
     * Exception message
     *
     * @var string
     */
    public $message;

    /**
     * IFRS Base Exception
     *
     * @param string $message
     * @param int $code
     */

    public function __construct(string $message = null, int $code = null)
    {
        Log::notice(
            $message,
            [
                'user_id' => Auth::check() ? Auth::user()->id : null,
                'time' => Carbon::now(),
            ]
        );
        parent::__construct($message ?: $this->message, $code, null);
    }
}
