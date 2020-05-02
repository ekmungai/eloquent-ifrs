<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Exceptions;

class PostedTransaction extends IFRSException
{
    /**
     * Posted Transaction Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("Cannot remove LineItem from a posted Transaction ").$message, $code);
    }
}
