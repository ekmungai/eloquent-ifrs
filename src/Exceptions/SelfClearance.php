<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Exceptions;

class SelfClearance extends IFRSException
{
    /**
     * Self Clearance Exception
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("Transaction cannot be used to clear itself ").$message, $code);
    }
}
