<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Transactions;

use Carbon\Carbon;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\Transaction;

use Ekmungai\IFRS\Interfaces\Buys;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Buying;
use Ekmungai\IFRS\Traits\Fetching;
use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Traits\Assigning;

class DebitNote extends AbstractTransaction implements Buys, Fetchable, Assignable
{
    use Buying;
    use Fetching;
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::DN;

    /**
     * Consctruct new DebitNote
     *
     * @param Account $account
     * @param Carbon $date
     * @param string $narration
     * @param Currency $currency
     * @param ExchangeRate $exchangeRate
     * @param string $reference
     *
     * @return AbstractTransaction
     */
    public static function new(
        Account $account,
        Carbon $date,
        string $narration,
        Currency $currency = null,
        ExchangeRate $exchangeRate = null,
        string $reference = null
    ) : AbstractTransaction {
        $debitNote = parent::instantiate(self::PREFIX);

        $debitNote->newTransaction(
            self::PREFIX,
            false,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $debitNote;
    }

    /**
     * Set DebitNote Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * DebitNote Unassigned Amount Balance
     *
     * @return float
     */
    public function balance(): float
    {
        return $this->transaction->balance();
    }
}
