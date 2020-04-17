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

use App\Interfaces\Fetchable;
use App\Interfaces\Buys;

use App\Traits\Buying;
use App\Traits\Fetching;

use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Transaction;

use App\Exceptions\MainAccount;

class CashPurchase extends AbstractTransaction implements Buys, Fetchable
{
    use Buying;
    use Fetching;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CP;

    /**
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = true;

    /**
     * Construct new CashPurchase
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

        $cashPurchase = parent::instantiate(self::PREFIX);

        $cashPurchase->newTransaction(
            self::PREFIX,
            self::CREDITED,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $cashPurchase;
    }

    /**
     * Set CashPurchase Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * Validate CashPurchase Main Account
     */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        $this->transaction->save();
    }
}
