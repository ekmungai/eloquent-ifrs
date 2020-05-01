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

use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Interfaces\Clearable;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Assigning;
use Ekmungai\IFRS\Traits\Clearing;
use Ekmungai\IFRS\Traits\Fetching;

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
     *
     */
    public function __construct($attributes = []) {

        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Set if JournalEntry Main Account is Credited in the Transaction
     *
     */
    public function setCredited(bool $credited): void
    {
        $this->credited = $credited;
    }
}
