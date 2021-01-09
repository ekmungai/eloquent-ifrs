<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;

use IFRS\Interfaces\Sells;
use IFRS\Interfaces\Fetchable;
use IFRS\Interfaces\Clearable;

use IFRS\Traits\Selling;
use IFRS\Traits\Fetching;
use IFRS\Traits\Clearing;

use IFRS\Models\Transaction;

class ClientInvoice extends Transaction implements Sells, Fetchable, Clearable
{
    use Selling;
    use Fetching;
    use Clearing;

    use \Parental\HasParent;

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
