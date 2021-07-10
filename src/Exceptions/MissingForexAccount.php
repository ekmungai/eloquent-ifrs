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

class MissingForexAccount extends IFRSException
{

    /**
     * Missing Forex Account Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = "A Forex Differences Account of type '" . Account::getType(Account::NON_OPERATING_REVENUE) . "' is required for Assignment Transactions with different exchange rates";

        parent::__construct($error . $message, $code);
    }
}
