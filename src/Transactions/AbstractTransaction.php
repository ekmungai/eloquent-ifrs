<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Transactions;

use Carbon\Carbon;

use Ekmungai\IFRS\Interfaces\Findable;
use Ekmungai\IFRS\Interfaces\Instantiable;

use Ekmungai\IFRS\Traits\Finding;
use Ekmungai\IFRS\Traits\Instantiating;

use Ekmungai\IFRS\Models\LineItem;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Transaction;

/**
 *
 * @codeCoverageIgnore
 *
 */
abstract class AbstractTransaction implements Instantiable, Findable
{
    use Instantiating;
    use Finding;

    /**
     * Transaction Object
     *
     * @var Transaction
     */

    protected $transaction;

    /**
     * Construct IFRS Transaction with a new Transaction Object
     *
     * @param string $prefix
     * @param bool $credited
     * @param Account $account
     * @param Carbon $date
     * @param string $narration
     * @param Currency $currency
     * @param ExchangeRate $exchangeRate
     * @param string $reference
     *
     *
     */
    public function newTransaction(
        string $prefix,
        bool $credited,
        Account $account,
        Carbon $date,
        string $narration,
        Currency $currency = null,
        ExchangeRate $exchangeRate = null,
        string $reference = null
    ) : void {
        $this->transaction = Transaction::new($account, $date, $narration, $currency, $exchangeRate, $reference);


    }

    /**
     * Add existing Transaction Object to IFRS Transaction
     *
     * @param Transaction $transaction
     */
    public function existingTransaction(Transaction $transaction) : void
    {
        $this->transaction = $transaction;
    }

    /**
     * Reload Transaction Model from the Database
     */
    public function refresh() : void
    {
        $this->transaction = Transaction::find($this->getId());
    }

    /**
     * isPosted analog for Assignment model.
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->transaction->isPosted();
    }

    /**
     * getClearedType analog for Assignment model.
     *
     * @return string
     */
    public function getClearedType() : string
    {
        return $this->transaction->getClearedType();
    }

    /**
     * isCredited analog for Assignment model.
     *
     * @return bool
     */
    public function isCredited()
    {
        return $this->transaction->isCredited();
    }

    /**
     * Check Transaction Integrity..
     *
     * @return bool
     */
    public function checkIntegrity()
    {
        return $this->transaction->checkIntegrity();
    }

    /**
     * Save Transaction
     */
    public function save(): void
    {
        $this->transaction->save();
    }

    /**
     * Delete Transaction
     *
     * @return bool
     */
    public function delete(): bool
    {
        return $this->transaction->delete();
    }

    /**
     * Post Transaction to the Ledger
     */
    public function post(): void
    {
        $this->transaction->post();
    }

    /**
     * get Transaction Id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->transaction->id;
    }

    /**
     * Get Transaction Number
     *
     * @return string
     */
    public function getTransactionNo(): string
    {
        return $this->transaction->transaction_no;
    }

    /**
     * Set Transaction Number
     *
     * @param string $transactionNo
     */
    public function setTransactionNo(string $transactionNo): void
    {
        $this->transaction->transaction_no = $transactionNo;
    }

    /**
     * Get Transaction Date
     *
     * @return string
     */
    public function getDate(): string
    {
        return $this->transaction->date;
    }

    /**
     * Get Transaction Main Account
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->transaction->account;
    }

    /**
     * Set Transaction Main Account
     *
     * @param Account $account
     */
    public function setAccount(Account $account): void
    {
        $this->transaction->account_id = $account->id;
    }

    /**
     * Get Transaction Currency
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->transaction->currency;
    }

    /**
     * Set Transaction Currency
     *
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->transaction->currency_id = $currency->id;
    }

    /**
     * Get Transaction Exchange Rate
     *
     * @return ExchangeRate
     */
    public function getExchangeRate(): ExchangeRate
    {
        return $this->transaction->exchangeRate;
    }

    /**
     * Set Transaction Exchange Rate
     *
     * @param ExchangeRate $exchangeRate
     */
    public function setExchangeRate(ExchangeRate $exchangeRate): void
    {
        $this->transaction->exchange_rate_id = $exchangeRate->id;
    }

    /**
     * Get Transaction Narration
     *
     * @return string
     */
    public function getNarration(): string
    {
        return $this->transaction->narration;
    }

    /**
     * Set Transaction Exchange Rate
     *
     * @param string $narration
     */
    public function setNarration(string $narration): void
    {
        $this->transaction->narration = $narration;
    }

    /**
     * Get Transaction Reference
     *
     * @return string
     */
    public function getReference(): string
    {
        return $this->transaction->reference;
    }

    /**
     * Set Transaction Reference
     *
     * @param string $reference
     */
    public function setReference(string $reference): void
    {
        $this->transaction->reference = $reference;
    }

    /**
     * Add LineItem to Transaction
     *
     * @param LineItem $lineItem
     */
    public function addLineItem(LineItem $lineItem): void
    {
        $this->transaction->addLineItem($lineItem);
    }

    /**
     * Remove LineItem from Transaction
     *
     * @param LineItem $lineItem
     */
    public function removeLineItem(LineItem $lineItem): void
    {
        $this->transaction->removeLineItem($lineItem);
    }

    /**
     * Get Transaction LineItems
     *
     * @return array
     */
    public function getLineItems(): array
    {
        return $this->transaction->getLineItems();
    }

    /**
     * Get Transaction Total Amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        $amount = 0;

        if ($this->isPosted()) {
            foreach ($this->transaction->ledgers->where("entry_type",Balance::DEBIT) as $ledger) {
                $amount += $ledger->amount / $this->transaction->exchangeRate->rate;
            }
        }else {
            foreach ($this->getLineItems() as $lineItem) {
                $amount += $lineItem->amount;
                $amount += $lineItem->amount * $lineItem->vat->rate / 100;
            }
        }
        return $amount;
    }
}
