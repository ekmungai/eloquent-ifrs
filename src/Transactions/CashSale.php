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

use Ekmungai\IFRS\Interfaces\Sells;
use Ekmungai\IFRS\Interfaces\Fetchable;

use Ekmungai\IFRS\Traits\Selling;
use Ekmungai\IFRS\Traits\Fetching;

use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\Transaction;
use Ekmungai\IFRS\Models\Account;

use Ekmungai\IFRS\Exceptions\MainAccount;

class CashSale extends AbstractTransaction implements Sells, Fetchable
{
    use Selling;
    use Fetching;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CS;

    /**
     * Construct new CashSale
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
        $cashSale = parent::instantiate(self::PREFIX);

        $cashSale->newTransaction(
            self::PREFIX,
            false,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $cashSale;
    }

    /**
     * Set CashSale Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * Validate CashSale Main Account
     */
    public function save(): void
    {
        if (is_null($this->getAccount()) or $this->getAccount()->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        $this->transaction->save();
    }
}
