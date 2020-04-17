<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Transactions;

use Carbon\Carbon;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\ExchangeRate;

use App\Interfaces\Assignable;
use App\Interfaces\Clearable;
use App\Interfaces\Fetchable;

use App\Traits\Assigning;
use App\Traits\Clearing;
use App\Traits\Fetching;

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
