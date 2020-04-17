<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Reports;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\ReportingPeriod;
use App\Models\Ledger;

use App\Exceptions\MissingAccount;
use Illuminate\Database\Query\Builder;

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
     * Get Statement Currency
     *
     * @return \App\Models\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set Statement Currency
     *
     * @param \App\Models\Currency $currency
     */
    public function setCurrency($currency) : void
    {
        $this->currency = $currency;
    }

    /**
     * Get Statement Account
     *
     * @return \App\Models\Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set Statement Account
     *
     * @param \App\Models\Account $account
     */
    public function setAccount($account) : void
    {
        $this->account = $account;
    }

    /**
     * Get Statement Entity
     *
     * @return \App\Models\Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set Statement Entity
     *
     * @param \App\Models\Entity $entity
     */
    public function setEntity($entity) : void
    {
        $this->entity = $entity;
    }

    /**
     * Build Statement Query
     *
     * @return Builder
     */
    public function buildQuery()
    {
        $query = DB::table('transactions')->leftJoin('ledgers', 'transactions.id', '=', 'ledgers.transaction_id')
        ->where('transactions.deleted_at', null)
        ->where("transactions.entity_id", $this->entity->id)
        ->where("transactions.date", ">=", $this->period['startDate'])
        ->where("transactions.date", "<=", $this->period['endDate'])
        ->where("transactions.currency_id", $this->currency->id)
        ->select(
            'transactions.id',
            'transactions.date',
            'transactions.transaction_no',
            'transactions.reference',
            'transactions.transaction_type',
            'transactions.narration'
        )->distinct();

        $account = $this->account;
        $query->where(function ($query) use ($account) {
            $query->where("ledgers.post_account", $this->account->id)
            ->orwhere("ledgers.folio_account", $this->account->id);
        });

        return $query;
    }

    /**
     * Print Account Statement attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [
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
     * @param Account $account
     * @param Currency $currency
     * @param Carbon $startDate
     * @param Carbon $endDate
     */
    public function __construct(
        Account $account = null,
        Currency $currency = null,
        Carbon $startDate = null,
        Carbon $endDate = null
    ) {
        if (is_null($account)) {
            throw new MissingAccount("Account Statement");
        }

        $this->entity = Auth::user()->entity;
        $this->account = $account;

        $this->period['startDate'] = is_null($startDate)? ReportingPeriod::periodStart(): $startDate;
        $this->period['endDate'] = is_null($endDate)? Carbon::now(): $endDate;
        $this->currency = is_null($currency)? $this->entity->currency: $currency;
    }

    /**
     * Get Account Statement Transactions.
     */
    public function getTransactions() : void
    {
        $query = $this->buildQuery();
        $this->balances['opening'] = $this->account->openingBalance((string)ReportingPeriod::year($this->period['startDate']));
        $this->balances['closing'] += $this->balances['opening'];

        $balance = $this->balances['opening'];
        foreach ($query->get() as $transaction) {
            $transaction->debit = $transaction->credit = 0;

            $contribution = Ledger::contribution($this->account, $transaction->id);
            $this->balances['closing'] += $contribution;
            $balance += $contribution;
            $transaction->balance = $balance;

            $contribution > 0 ? $transaction->debit = abs($contribution) : $transaction->credit = abs($contribution);

            $transaction->transaction_type = config('ifrs')['transactions'][$transaction->transaction_type];

            array_push($this->transactions, $transaction);
        }
    }
}
