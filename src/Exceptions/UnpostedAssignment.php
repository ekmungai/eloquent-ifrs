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
class UnpostedAssignment extends IFRSException
{
    /**
     * Unposted Assignment Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("An Unposted Transaction cannot be Assigned or Cleared").$message, $code);
    }
}
