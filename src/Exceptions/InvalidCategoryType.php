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

class InvalidCategoryType extends IFRSException
{

    /**
     * Invalid Category Type Exception
     *
     * @param string $accountType
     * @param string $categoryType
     * @param string $message
     * @param int $code
     */
    public function __construct(string $accountType, string $categoryType, ?string $message = null, ?int $code = null)
    {
        $error = "Cannot assign " . Account::getType($accountType) . " Account to " . Account::getType($categoryType) . " Category";

        parent::__construct($error . ' ' . $message, $code);
    }
}
