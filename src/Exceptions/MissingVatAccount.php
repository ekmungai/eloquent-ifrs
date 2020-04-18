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
class MissingVatAccount extends IFRSException
{
    /**
     * Missing Vat Account Exception
     *
     * @param string $vatName
     * @param string $message
     * @param int $code
     */
    public function __construct(string $vatName, string $message = null, int $code = 0)
    {
        parent::__construct($vatName._(" LineItem requires a Vat Account").$message, $code);
    }
}
