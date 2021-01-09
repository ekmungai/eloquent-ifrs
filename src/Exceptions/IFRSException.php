<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Exceptions;

abstract class IFRSException extends \Exception
{
    /**
     * Exception code
     *
     * @var int
     */
    public $code;

    /**
     * Exception message
     *
     * @var string
     */
    public $message;

    /**
     * IFRS Base Exception
     *
     * @param string $message
     * @param int    $code
     */

    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct($message ?: $this->message, $code, null);
    }
}
