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
use App\Models\ExchangeRate;
use App\Models\Transaction;

use App\Interfaces\Sells;
use App\Interfaces\Fetchable;

use App\Traits\Selling;
use App\Traits\Fetching;
use App\Interfaces\Assignable;
use App\Traits\Assigning;

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
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = true;

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
            self::CREDITED,
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
