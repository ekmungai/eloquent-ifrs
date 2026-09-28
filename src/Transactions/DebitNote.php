<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;

use IFRS\Interfaces\Buys;

use IFRS\Traits\Buying;

use IFRS\Models\Transaction;

class DebitNote extends Transaction implements Buys
{
    use Buying;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::DN;

    /**
     * Construct new ContraEntry
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
