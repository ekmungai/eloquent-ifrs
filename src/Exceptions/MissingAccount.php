<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

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
        $error = $statementType . " Transactions require an Account ";

        parent::__construct($error . $message, $code);
    }
}
