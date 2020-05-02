<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Transactions;

use IFRS\Models\Transaction;

use IFRS\Interfaces\Sells;
use IFRS\Interfaces\Fetchable;

use IFRS\Traits\Selling;
use IFRS\Traits\Fetching;
use IFRS\Interfaces\Assignable;
use IFRS\Traits\Assigning;

class CreditNote extends Transaction implements Sells, Fetchable, Assignable
{
    use Selling;
    use Fetching;
    use Assigning;

    use \Parental\HasParent;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CN;

    /**
     * Construct new ContraEntry
     *
     * @param array $attributes
     *
     */
    public function __construct($attributes = []) {

        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
