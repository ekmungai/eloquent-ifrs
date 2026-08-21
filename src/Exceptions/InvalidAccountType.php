<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

use IFRS\Models\Account;

class InvalidAccountType extends IFRSException
{

    /**
     * Invalid Account Type Exception
     *
     * @param string $accountName
     * @param array|string $accountTypes
     * @param string $message
     * @param int $code
     */
    public function __construct($accountName, $accountTypes, ?string $message = null, ?int $code = null)
    {
        $error = $accountName . ' Account';
        if (is_array($accountTypes)) {
            $accountTypes = Account::getTypes($accountTypes);
            $error .= " Type must be one of: " . implode(", ", $accountTypes);
        } else {
            $accountTypes = Account::getType($accountTypes);
            $error .= " must be of Type " . $accountTypes;
        }

        parent::__construct($error . ' ' . $message, $code);
    }
}
