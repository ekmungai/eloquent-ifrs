<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

use Ekmungai\IFRS\Models\Transaction;

/**
 *
 * @author emung
 *
 */
class VatCharge extends IFRSException
{
    /**
     * Vat Charge Exception
     *
     * @param string $transactionType
     * @param string $message
     * @param int $code
     */
    public function __construct($transactionType, string $message = null, int $code = 0)
    {
        $transactionType = Transaction::getType($transactionType);

        parent::__construct($transactionType._(" LineItems cannot be Charged VAT ").$message, $code);
    }
}
