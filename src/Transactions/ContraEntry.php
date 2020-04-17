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

use App\Interfaces\Fetchable;

use App\Traits\Fetching;

use App\Exceptions\MainAccount;
use App\Exceptions\LineItemAccount;
use App\Exceptions\VatCharge;

class ContraEntry extends AbstractTransaction implements Fetchable
{
    use Fetching;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CE;

    /**
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = false;

    /**
     * Consctruct new ContraEntry
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

        $contraEntry = parent::instantiate(self::PREFIX);

        $contraEntry->newTransaction(
            self::PREFIX,
            self::CREDITED,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $contraEntry;
    }

    /**
     * Set ContraEntry Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
    * Validate ContraEntry Main Account
    */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        $this->transaction->save();
    }

    /**
     * Validate ClientReceipt LineItems
     */
    public function post(): void
    {
        $this->save();

        foreach ($this->getLineItems() as $lineItem) {
            if ($lineItem->account->account_type != Account::BANK) {
                throw new LineItemAccount(self::PREFIX, [Account::BANK]);
            }

            if ($lineItem->vat->rate > 0) {
                throw new VatCharge(self::PREFIX);
            }
        }

        $this->transaction->post();
    }
}
