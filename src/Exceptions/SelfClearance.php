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
class SelfClearance extends IFRSException
{
    /**
     * Self Clearance Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = 0)
    {
        parent::__construct(_("Transaction cannot be used to clear itself ").$message, $code);
    }
}
