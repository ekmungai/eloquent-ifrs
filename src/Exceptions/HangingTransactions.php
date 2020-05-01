<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT See LICENSE.md
 */
namespace Ekmungai\IFRS\Exceptions;

class HangingTransactions extends IFRSException
{
    /**
     * Hanging Transactions Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $error = _("Account cannot be deleted because it has existing transactions in the current Reporting Period ");

        parent::__construct($error.$message, $code);
    }
}
