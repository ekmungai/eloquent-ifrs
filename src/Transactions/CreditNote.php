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

use Ekmungai\IFRS\Interfaces\Sells;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Selling;
use Ekmungai\IFRS\Traits\Fetching;
use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Traits\Assigning;

class CreditNote extends AbstractTransaction implements Sells, Fetchable, Assignable
{
    use Selling;
    use Fetching;
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CN;

    /**
     * Consctruct new CreditNote
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
        $creditNote = parent::instantiate(self::PREFIX);

        $creditNote->newTransaction(
            self::PREFIX,
            true,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $creditNote;
    }

    /**
     * Set CreditNote Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * CreditNote Unassigned Amount Balance
     *
     * @return float
     */
    public function balance(): float
    {
        return $this->transaction->balance();
    }
}
