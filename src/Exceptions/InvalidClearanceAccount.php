<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

class InvalidClearanceAccount extends IFRSException
{
    /**
     * Invalid Clearance Account Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("Assignment and Clearance Main Account must be the same ").$message, $code);
    }
}
