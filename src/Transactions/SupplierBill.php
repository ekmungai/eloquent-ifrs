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
use Ekmungai\IFRS\Interfaces\Clearable;
use Ekmungai\IFRS\Traits\Clearing;

class SupplierBill extends AbstractTransaction implements Buys, Fetchable, Clearable
{
    use Buying;
    use Fetching;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::BL;

    /**
     * Construct new SupplierBill
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
        $supplierBill = parent::instantiate(self::PREFIX);

        $supplierBill->newTransaction(
            self::PREFIX,
            true,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $supplierBill;
    }

    /**
     * Set SupplierBill Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * SupplierBill Amount that has been Cleared
     *
     * @return float
     */
    public function clearedAmount(): float
    {
        return $this->transaction->clearedAmount();
    }
}
