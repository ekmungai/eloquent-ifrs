<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Exceptions;

class MissingAccount extends IFRSException
{
    /**
     * Missing Account Exception
     *
     * @param $statementType
     * @param string $message
     * @param int $code
     */
    public function __construct(string $statementType, string $message = null, int $code = null)
    {
        parent::__construct($statementType._(" Transactions require an Account ").$message, $code);
    }
}
