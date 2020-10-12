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
        "clearedAmount" => 0,
        "unclearedAmount" => 0,
    ];

    /**
     * Get Transaction amounts.
     *
     * @param Transaction|Balance $transaction
     * @param string              $transactionType
     */
    private function getAmounts($transaction): void
    {
        if ($transaction instanceof Balance) {
            $transaction->transactionType = Transaction::getType($transaction->transaction_type);
        } else {
            $transaction->transactionType = $transaction->type;
        }

        $transaction->originalAmount = $transaction->amount;
        $transaction->clearedAmount = $transaction->cleared_amount;
        $unclearedAmount = $transaction->originalAmount - $transaction->clearedAmount;

        if ($unclearedAmount > 0) {

            $date = Carbon::parse($transaction->transaction_date);
            $transaction->age  = $date->diffInDays($this->period['endDate']);

            $this->balances["originalAmount"] += $transaction->originalAmount;
            $this->balances['clearedAmount'] += $transaction->clearedAmount;
            $this->balances['unclearedAmount'] += $unclearedAmount;

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
    public function getTransactions(): void
    {
        // Opening Balances
        foreach ($this->account->balances->where(
            "reporting_period_id",
            ReportingPeriod::getPeriod($this->period['endDate'])->id
        ) as $balance) {
            $this->getAmounts($balance, _("Opening Balance"));
        }

        // Clearable Transactions
        $transactions = $this->account->transactionsQuery(
            $this->period['startDate'],
            $this->period['endDate']
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
    }
}
