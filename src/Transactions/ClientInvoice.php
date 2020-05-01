<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Transactions;

use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Interfaces\Sells;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Selling;
use Ekmungai\IFRS\Traits\Fetching;
use Ekmungai\IFRS\Interfaces\Clearable;
use Ekmungai\IFRS\Traits\Clearing;

class ClientInvoice extends Transaction implements Sells, Fetchable, Clearable
{
    use Selling;
    use Fetching;
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
     *
     */
    public function __construct($attributes = []) {

        $attributes['credited'] = false;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
