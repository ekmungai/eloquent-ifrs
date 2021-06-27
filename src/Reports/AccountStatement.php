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

use Illuminate\Support\Facades\Auth;

use IFRS\Models\Entity;
use IFRS\Models\Ledger;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\ReportingPeriod;

use IFRS\Exceptions\MissingAccount;

class AccountStatement
{
    /**
     * Account Statement Currency.
     *
     * @var Currency
     */
    protected $currency;

    /**
     * Account Statement Account.
     *
     * @var Account
     */
    protected $account;

    /**
     * Account Statement Entity.
     *
     * @var Entity
     */
    protected $entity;

    /**
     * Account Statement balances.
     *
     * @var array
     */
    public $balances = [
        "opening" => 0,
        "closing" => 0
    ];

    /**
     * Account Statement period.
     *
     * @var array
     */
    public $period = [
        "startDate" => null,
        "endDate" => null
    ];

    /**
     * Account Statement transactions.
     *
     * @var array
     */
    public $transactions = [];

    /**
     * Print Account Statement attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) [
            "Account" => $this->account->name,
            "Currency" => $this->currency->name,
            "Entity" => $this->entity->name,
            "Period" => $this->period,
            "Balances" => $this->balances,
            "Transactions" => $this->transactions
        ];
    }

    /**
     * Construct Account Statement for the account for the period.
     *
     * @param int    $accountId
     * @param int    $currencyId
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(
        int $accountId = null,
        int $currencyId = null,
        string $startDate = null,
        string $endDate = null,
        Entity $entity = null
    ) {
        if (is_null($accountId)) {
            throw new MissingAccount("Account Statement");
        } else {
            $this->account = Account::find($accountId);
        }

        if(is_null($entity)){
            $this->entity = Auth::user()->entity;
        }else{
            $this->entity = $entity;
        }


        $this->period['startDate'] = is_null($startDate) ? ReportingPeriod::periodStart(null,$entity) : Carbon::parse($startDate);
        $this->period['endDate'] = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);
        $this->currency = is_null($currencyId) ? $this->entity->currency : Currency::find($currencyId);
        $this->currencyId = $currencyId;
    }

    /**
     * Get Account Statement Transactions.
     */
    public function getTransactions(): array
    {
        $query = $this->account->transactionsQuery($this->period['startDate'], $this->period['endDate'], $this->currencyId);

        $this->balances['opening'] = $this->account->openingBalance(ReportingPeriod::year($this->period['startDate'],$this->entity), $this->currencyId,$this->entity->id)[$this->currency->id];
        $this->balances['closing'] += $this->balances['opening'];

        $balance = $this->balances['opening'];

        foreach ($query->get() as $transaction) {
            $transaction->debit = $transaction->credit = 0;

            $contribution = Ledger::contribution($this->account, $transaction->id, $this->currencyId);
            $this->balances['closing'] += $contribution;
            $balance += $contribution;
            $transaction->balance = $balance;

            $contribution > 0 ? $transaction->debit = abs($contribution) : $transaction->credit = abs($contribution);

            $transaction->transactionType = config('ifrs')['transactions'][$transaction->transaction_type];
            $transaction->transactionDate = Carbon::parse($transaction->transaction_date)->toFormattedDateString();

            array_push($this->transactions, $transaction);
        }

        return $this->transactions;
    }
}
