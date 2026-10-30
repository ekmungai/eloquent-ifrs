<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

class EntityMismatch extends IFRSException
{
    /**
     * Entity Mismatch Exception
     *
     * @param string $entityName
     * @param string $message
     * @param int $code
     */
    public function __construct(string $entityName, ?string $message = null, ?int $code = null)
    {
        $error = "Entity '" . $entityName . "' is not the Entity of the logged in User ";

        parent::__construct($error . $message, $code);
    }
}
