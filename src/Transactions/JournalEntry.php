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
use IFRS\Interfaces\Fetchable;
use IFRS\Interfaces\Assignable;

use IFRS\Traits\Clearing;
use IFRS\Traits\Fetching;
use IFRS\Traits\Assigning;

use IFRS\Models\Transaction;

/**
 * Class JournalEntry
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property ExchangeRate $exchangeRate
 * @property Account $account
 * @property Currency $currency
 * @property Carbon $date
 * @property string $reference
 * @property string $transaction_no
 * @property string $transaction_type
 * @property string $narration
 * @property bool $credited
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */

class JournalEntry extends Transaction implements Assignable, Clearable, Fetchable
{
    use Assigning;
    use Clearing;
    use Fetching;

    use \Parental\HasParent;

    /**
     * Transaction Number prefix
     *
     * @var string
     */
    const PREFIX = Transaction::JN;

    /**
     * Construct new JournalEntry
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        if (!isset($attributes['credited'])) {
            $attributes['credited'] = true;
        }
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
