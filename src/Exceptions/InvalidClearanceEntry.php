<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

/**
 *
 * @author emung
 *
 */
class InvalidClearanceEntry extends IFRSException
{

    /**
     * Invalid Clearance Entry Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = 0)
    {
        $error = _("Transaction Entry increases the Main Account outstanding balance instead of reducing it ");

        parent::__construct($error.$message, $code);
    }
}
