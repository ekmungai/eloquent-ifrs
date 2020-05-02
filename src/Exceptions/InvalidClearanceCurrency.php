<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Exceptions;

class InvalidClearanceCurrency extends IFRSException
{
    /**
     * Invalid Clearance Currency Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("Assignment and Clearance Currency must be the same").$message, $code);
    }
}
