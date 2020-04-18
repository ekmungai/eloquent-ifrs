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
use Ekmungai\IFRS\Models\Transaction;
use Ekmungai\IFRS\Models\ExchangeRate;

use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Interfaces\Clearable;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Assigning;
use Ekmungai\IFRS\Traits\Clearing;
use Ekmungai\IFRS\Traits\Fetching;

class JournalEntry extends AbstractTransaction implements Assignable, Clearable, Fetchable
{
    use Assigning;
    use Clearing;
    use Fetching;

    /**
     * Transaction Number prefix
     *
     * @var string
     */
    const PREFIX = Transaction::JN;

    /**
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = true;

    /**
     * Construct new JournalEntry
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
        $journalEntry = parent::instantiate(self::PREFIX);

        $journalEntry->newTransaction(
            self::PREFIX,
            self::CREDITED,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $journalEntry;
    }

    /**
     * Set JournalEntry Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * Get if JournalEntry Main Account is Credited in the Transaction
     *
     * @return bool
     */
    public function getCredited(): bool
    {
        return $this->transaction->credited;
    }

    /**
     * Set if JournalEntry Main Account is Credited in the Transaction
     *
     */
    public function setCredited(bool $credited): void
    {
        $this->transaction->credited = $credited;
    }

    /**
     * JournalEntry Unassigned Amount Balance
     *
     * @return float
     */
    public function balance(): float
    {
        return $this->transaction->balance();
    }

    /**
     * JournalEntry Amount that has been Cleared
     */
    public function clearedAmount(): float
    {
        return $this->transaction->clearedAmount();
    }
}
