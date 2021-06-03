<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Reports;

use Carbon\Carbon;

use IFRS\Models\Balance;
use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\Transaction;
use IFRS\Models\ReportingPeriod;

use IFRS\Exceptions\MissingAccount;
use IFRS\Exceptions\InvalidAccountType;

class AccountSchedule extends AccountStatement
{

    /**
     * Account Schedule balances.
     *
     * @var array
     */
    public $balances = [
        "originalAmount" => 0,
        "amountCleared" => 0,
        "unclearedAmount" => 0,
        "totalAge" => 0,
    ];

    /**
     * Get Transaction amounts.
     *
     * @param Transaction|Balance $transaction
     * @param string              $transactionType
     */
    private function getAmounts($transaction): void
    {
        $rate = is_null($this->currencyId) ? $transaction->exchangeRate->rate : 1;

        $transaction->originalAmount = $transaction->amount * $rate;
        $transaction->amountCleared= $transaction->clearedAmount * $rate;
        $unclearedAmount = $transaction->originalAmount - $transaction->clearedAmount * $rate;

        if ($unclearedAmount > 0) {

            if ($transaction instanceof Balance) {
                $transaction->transactionType = Transaction::getType($transaction->transaction_type);
            } else {
                $transaction->transactionType = $transaction->type;
            }

            $date = Carbon::parse($transaction->transaction_date);
            $transaction->age  = $date->diffInDays($this->period['endDate']);
            $transaction->transactionDate = Carbon::parse($transaction->transaction_date)->toFormattedDateString();

            $this->balances["originalAmount"] += $transaction->originalAmount;
            $this->balances['amountCleared'] += $transaction->amountCleared;
            $this->balances['unclearedAmount'] += $unclearedAmount;
            $this->balances['totalAge'] += $transaction->age;

            $transaction->unclearedAmount = $unclearedAmount;

            array_push($this->transactions, $transaction);
        }
    }

    /**
     * Account Schedule for the account for the period.
     *
     * @param int    $accountId
     * @param int    $currencyId
     * @param string $endDate
     */
    public function __construct(int $accountId = null, int $currencyId = null, string $endDate = null)
    {
        if (is_null($accountId)) {
            throw new MissingAccount("Account Schedule");
        }

        $accountTypes = [Account::RECEIVABLE, Account::PAYABLE];

        if (!in_array(Account::find($accountId)->account_type, $accountTypes)) {
            throw new InvalidAccountType($accountTypes);
        }
        parent::__construct($accountId, $currencyId, null, $endDate);
    }

    /**
     * Get Account Schedule Transactions.
     */
    public function getTransactions(): array
    {
        $periodId = ReportingPeriod::getPeriod($this->period['endDate'])->id;
        $currencyId = $this->currencyId;

        // Opening Balances
        $balances = $this->account->balances->filter(function ($balance, $key) use ($periodId) {
            return $balance->reporting_period_id == $periodId;
        });

        if (!is_null($currencyId)) {
            $balances = $this->account->balances->filter(function ($balance, $key) use ($currencyId) {
                return $balance->currency_id == $currencyId;
            });
        }

        foreach ($balances as $balance) {
            $this->getAmounts($balance, _("Opening Balance"));
        }

        // Clearable Transactions
        $transactions = $this->account->transactionsQuery(
            $this->period['startDate'],
            $this->period['endDate'], 
            $this->currencyId
        )->whereIn(
            'transaction_type',
            Assignment::CLEARABLES
        )->select(config('ifrs.table_prefix') . 'transactions.id');

        foreach ($transactions->get() as $transaction) {
            $transaction = Transaction::find($transaction->id);

            if (
                $transaction->transaction_type == Transaction::JN
                && (($this->account->account_type == Account::RECEIVABLE && $transaction->is_credited)
                    || ($this->account->account_type == Account::PAYABLE && !$transaction->is_credited))
            ) {
                continue;
            }
            $this->getAmounts($transaction);
        }
        $totaltransactions = count($this->transactions);
        if ($totaltransactions > 0) {
            $this->balances['averageAge'] = round($this->balances['totalAge'] / $totaltransactions, 0);
        }
        
        return $this->transactions;
    }
}
