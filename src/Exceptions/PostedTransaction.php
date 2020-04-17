<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Exceptions;

/**
 *
 * @author emung
 *
 */
class PostedTransaction extends IFRSException
{
    /**
     * Posted Transaction Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = 0)
    {
        parent::__construct(_("Cannot remove LineItem from a posted Transaction ").$message, $code);
    }
}
