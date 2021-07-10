<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class UnauthorizedUser extends IFRSException
{
    /**
     * Unauthorized User Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = 'You are not Authorized to perform that action ';

        parent::__construct($error . $message, $code);
    }
}
