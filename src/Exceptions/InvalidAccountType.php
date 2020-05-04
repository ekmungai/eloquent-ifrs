<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Exceptions;

use IFRS\Models\Account;

class InvalidAccountType extends IFRSException
{

    /**
     * Invalid Account Type Exception
     *
     * @param array $accountTypes
     * @param string $message
     * @param int $code
     */
    public function __construct(array $accountTypes, string $message = null, int $code = null)
    {
        $accountTypes = Account::getTypes($accountTypes);

        $error = _("Schedule Account Type must be one of: ").implode(", ", $accountTypes);

        parent::__construct($error.' '.$message, $code);
    }
}
