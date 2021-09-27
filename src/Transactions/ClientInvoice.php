<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;


use IFRS\Interfaces\Clearable;
use IFRS\Interfaces\Sells;

use IFRS\Traits\Selling;
use IFRS\Traits\Clearing;

use IFRS\Models\Transaction;

class ClientInvoice extends Transaction implements Sells, Clearable
{
    use Selling;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::IN;

    /**
     * Construct new ClientInvoice
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = false;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
