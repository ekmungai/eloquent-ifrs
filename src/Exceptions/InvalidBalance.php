<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Exceptions;

use App\Models\Balance;

/**
 *
 * @author emung
 *
 */
class InvalidBalance extends IFRSException
{

    /**
     * Invalid Balance Exception
     *
     * @param array $balanceTypes
     * @param string $message
     * @param int $code
     */
    public function __construct(array $balanceTypes, string $message = null, int $code = null)
    {
        $balanceTypes = Balance::getTypes($balanceTypes);

        $error = _("Opening Balance Type must be one of: ").implode(", ", $balanceTypes);

        parent::__construct($error.' '.$message, $code);
    }
}
