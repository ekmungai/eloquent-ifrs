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
use App\Interfaces\Assignable;
use App\Traits\Assigning;

class SupplierPayment extends AbstractTransaction implements Fetchable, Assignable
{
    use Fetching;
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::PY;

    /**
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = false;

    /**
     * Construct new SupplierPayment
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

        $supplierPayment = parent::instantiate(self::PREFIX);

        $supplierPayment->newTransaction(
            self::PREFIX,
            self::CREDITED,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $supplierPayment;
    }

    /**
     * Set SupplierPayment Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * Validate SupplierPayment Main Account
     */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::PAYABLE) {
            throw new MainAccount(self::PREFIX, Account::PAYABLE);
        }

        $this->transaction->save();
    }

    /**
     * Validate SupplierPayment LineItems
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

    /**
     * SupplierPayment Unassigned Amount Balance
     *
     * @return float
     */
    public function balance(): float
    {
        return $this->transaction->balance();
    }
}
