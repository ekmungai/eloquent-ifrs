<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Transactions;

use IFRS\Models\Transaction;

use IFRS\Interfaces\Buys;
use IFRS\Interfaces\Fetchable;

use IFRS\Traits\Buying;
use IFRS\Traits\Fetching;
use IFRS\Interfaces\Clearable;
use IFRS\Traits\Clearing;

class SupplierBill extends Transaction implements Buys, Fetchable, Clearable
{
    use Buying;
    use Fetching;
    use Clearing;

    use \Parental\HasParent;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::BL;

    /**
     * Construct new ContraEntry
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
