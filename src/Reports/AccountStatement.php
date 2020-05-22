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
use Illuminate\Support\Facades\DB;

use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Ledger;

use IFRS\Exceptions\MissingAccount;
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
     * Build Statement Query
     *
     * @return Builder
     */
    protected function buildQuery()
    {
        $query = DB::table('transactions')->leftJoin('ledgers', 'transactions.id', '=', 'ledgers.transaction_id')
            ->where('transactions.deleted_at', null)
            ->where("transactions.entity_id", $this->entity->id)
            ->where("transactions.transaction_date", ">=", $this->period['startDate'])
            ->where("transactions.transaction_date", "<=", $this->period['endDate'])
            ->where("transactions.currency_id", $this->currency->id)
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.transaction_no',
                'transactions.reference',
                'transactions.transaction_type',
                'transactions.narration'
            )->distinct();

        $query->where(
            function ($query){
                $query->where("ledgers.post_account", $this->account->id)
                    ->orwhere("ledgers.folio_account", $this->account->id);
            }
        );

        return $query;
    }

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
        string $endDate = null
    ) {
        if (is_null($accountId)) {
            throw new MissingAccount("Account Statement");
        } else {
            $this->account = Account::find($accountId);
        }

        $this->entity = Auth::user()->entity;

        $this->period['startDate'] = is_null($startDate)? ReportingPeriod::periodStart(): Carbon::parse($startDate);
        $this->period['endDate'] = is_null($endDate)? Carbon::now(): Carbon::parse($endDate);
        $this->currency = is_null($currencyId)? $this->entity->currency: Currency::find($currencyId);
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
