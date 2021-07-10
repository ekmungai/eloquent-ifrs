<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class MissingReportingCurrency extends IFRSException
{
    /**
     * Missing Reporting Currency Exception
     *
     * @param string $entity
     * @param string $message
     * @param int $code
     */
    public function __construct(string $entity, string $message = null, int $code = null)
    {
        $error = "Entity '" . $entity . "' has no Reporting Currency defined ";

        parent::__construct($error . $message, $code);
    }
}
