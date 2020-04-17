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
class MissingAccountType extends IFRSException
{
    /**
     * Missing Account Type Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct(_("Account type is Required ").$message, $code);
    }
}
