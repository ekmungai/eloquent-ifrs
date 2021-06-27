<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingReportingPeriod;
use IFRS\Exceptions\InvalidAccountType;
use IFRS\Exceptions\InvalidPeriodStatus;
use IFRS\Exceptions\MissingClosingRate;
use IFRS\Reports\BalanceSheet;
use IFRS\Transactions\JournalEntry;

/**
 * Class ReportingPeriod
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property integer $year
 * @property integer $period_count
 * @property string $status
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class ReportingPeriod extends Model implements Segregatable, Recyclable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * Reporting Period Status
     *
     * @var string
     */

    const OPEN = "OPEN";
    const CLOSED = "CLOSED";
    const ADJUSTING = "ADJUSTING";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'period_count',
        'calendar_year',
        'status',
        'entity_id',
    ];

    /**
     * Closing Rates.
     * 
     * @param int $forexAccountId
     * @param int $vatId
     * @param Account $account
     * @param float $localBalance
     * @param float $foreignBalance
     * @param Carbon $closingDate
     * @param object $currency
     * 
     * @return Transaction
     */
    private function balanceAccount(
        int $forexAccountId,
        int $vatId,
        Account $account, 
        float $localBalance, 
        float $foreignBalance, 
        Carbon $closingDate,
        object $currency
    ) : Transaction {

        $difference = $foreignBalance - $localBalance;
        $isAsset = in_array($account->account_type, config('ifrs')[BalanceSheet::ASSETS]);
        $isLiability = in_array($account->account_type, config('ifrs')[BalanceSheet::LIABILITIES]);

        if($isAsset && $difference > 0 || $isLiability && $difference < 0){
            $credited = true;
        }elseif($isAsset && $difference < 0 || $isLiability && $difference > 0){
            $credited = false;
        }

        $balanceTransaction = JournalEntry::create([
            "account_id" => $account->id,
            "transaction_date" => $closingDate,
            "narration" => $currency->currency_code . " ". $this->calendar_year . " Forex Balance Translation",
            "reference" => $currency->currency_code,
            "credited" => $credited
        ]);

        $balanceTransaction->addLineItem(LineItem::create([
            'vat_id' => $vatId,
            'account_id' => $forexAccountId,
            'amount' => abs($difference),
        ]));

        $balanceTransaction->save();
        
        ClosingTransaction::create([
            "reporting_period_id" => $this->id,
            "transaction_id" => $balanceTransaction->id,
            "currency_id" => $currency->currency_id
        ]);
        return $balanceTransaction;
    }

    /**
     * Construct new Reporting Period.
     */
    public function __construct($attributes = [])
    {
        if (!isset($attributes['status'])) {
            $attributes['status'] = ReportingPeriod::OPEN;
        }
        return parent::__construct($attributes);
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        return $type ? array_pop($classname) . ': ' . $this->calendar_year : $this->calendar_year;
    }

    /**
     * Fetch reporting period for the date
     *
     * @param string|Carbon $date
     * @return ReportingPeriod
     */
    public static function getPeriod($date = null,$entity = null)
    {

        if(Auth::user()){
            $entity = Auth::user()->entity;
        }

        $year = ReportingPeriod::year($date,$entity);
        $period = ReportingPeriod::where("calendar_year", $year)
                ->where('entity_id','=',$entity->id)->first();
        if (is_null($period)) {
            throw new MissingReportingPeriod($entity->name, $year);
        }
        return $period;
    }

    /**
     * ReportingPeriod year
     *
     * @param string | Carbon $date
     *
     * @return int
     */
    public static function year($date = null,$entity = null)
    {

        if(Auth::user()){
            $entity = Auth::user()->entity;
        }

        if(!Auth::user() && is_null($entity)){
            return date("Y");
        }

        $year = is_null($date) ? date("Y") : date("Y", strtotime($date));
        $month = is_null($date) ? date("m") : date("m", strtotime($date));

        $year  = intval($month) < $entity->year_start ? intval($year) - 1 : $year;

        return intval($year);
    }

    /**
     * ReportingPeriod start date
     *
     * @return Carbon $date
     */
    public static function periodStart($date = null,$entity = null)
    {

        if(Auth::user()){
            $entity = Auth::user()->entity;
        }

        if(!$entity){
          return  Carbon::parse(date("Y")."-01-01")->startOfDay();
        }

        return Carbon::create(
            ReportingPeriod::year($date,$entity),
            $entity->year_start,
            1
        )->startOfDay();

    }

    /**
     * ReportingPeriod end date
     *
     * @return Carbon
     */
    public static function periodEnd($date = null,$entity = null)
    {
        return ReportingPeriod::periodStart($date,$entity)
            ->addYear()
            ->subDay();
    }

    /**
     * Closing Rates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingRates()
    {
        return $this->hasMany(ClosingRate::class);
    }

    /**
     * Closing Transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingTransactions()
    {
        return $this->hasMany(ClosingTransaction::class);
    }

    /**
     * ReportingPeriod attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Retrieve the currencies used in trasactions for the period.
     * 
     * @param int $accountId
     * 
     * @return collection
     */
    public function transactionCurrencies($accountId = null)
    {
        $reportingCurrency = Auth::user()->entity->currency_id;
        $transactionTable = config('ifrs.table_prefix') . 'transactions';
        $currenciesTable = config('ifrs.table_prefix') . 'currencies';
        $query = DB::table($transactionTable)
            ->leftJoin($currenciesTable, $currenciesTable . '.id', '=', $transactionTable . '.currency_id')
            ->whereYear($transactionTable . '.transaction_date', '=', $this->calendar_year)
            ->whereNotIn($currenciesTable . '.id', [$reportingCurrency])
            ->select($transactionTable . '.currency_id', $currenciesTable . '.currency_code')
            ->distinct();

        if (!is_null($accountId)) {
            $query->where($transactionTable . '.account_id', $accountId);
        }

        return $query->get();
    }

    /**
     * Get the auto generated translation transactions for the period.
     * 
     * @return array
     */
    public function getTranslations() : array
    {
        $transactions = [];

        foreach($this->closingTransactions as $translation){
            $transaction = Transaction::find($translation->transaction_id);
            $account = $transaction->account;
            $balances = $account->closingBalance($transaction->transaction_date, $translation->currency_id);
            $closingRate  = ClosingRate::where('reporting_period_id', $this->id)
            ->whereHas('ExchangeRate', function($q) use ($translation) {
                $q->where('currency_id', $translation->currency_id);
            })->first()->exchangeRate->rate;
            $credited = $transaction->credited ? -1 : 1;
            $transactions[$account->name][] = [
                'currency' => $transaction->reference,
                'closingRate' => $closingRate,
                'currencyBalance' => $balances[$translation->currency_id],
                'localBalance' => $balances[$transaction->currency_id],
                'foreignBalance' => $balances[$translation->currency_id] * $closingRate,
                'translation' => $transaction->amount * $credited,
                'posted' => $transaction->is_posted
            ];
        }        
        return $transactions;
    }

    /**
     * Commit the auto generated translation transactions for the period to the ledger.
     * 
     * @return void
     */
    public function postTranslations() : void
    {
        foreach($this->closingTransactions as $translation){
            $transaction = Transaction::find($translation->transaction_id);
            $transaction->post();       
        }        
    }

    /**
     * Prepare Forex Account Balances translations.
     * 
     * @param int $forexAccountId
     * @param int $vatId
     * @param int $accountId
     * 
     * @return array $transactions
     */
    public function prepareBalancesTranslation($forexAccountId, int $vatId, int $accountId = null) : array
    {

        if (Account::find($forexAccountId)->account_type != Account::EQUITY) {
            throw new InvalidAccountType('Transaltion Forex', Account::EQUITY);
        }

        if ($this->status != ReportingPeriod::ADJUSTING) {
            throw new InvalidPeriodStatus();
        }

        $rates = $transactions = [];
        foreach($this->transactionCurrencies() as $currency){
            $closingRate = ClosingRate::where('reporting_period_id', $this->id)
            ->whereHas('ExchangeRate', function($q) use ($currency) {
                $q->where('currency_id', $currency->currency_id);
            });

            if($closingRate->count() == 0) {
                $currencyCode = Currency::find($currency->currency_id)->currency_code;
                throw new MissingClosingRate($currencyCode);
            }
            $rates[$currency->currency_id] = $closingRate->get()->first()->exchangerate->rate;
        }
        
        $reportingCurrency = Auth::user()->entity->currency_id;
        $periodEnd = ReportingPeriod::periodEnd($this->calendar_year.'01-01');

        $accounts = Account::whereNotIn('currency_id', [$reportingCurrency]);
        if(!is_null($accountId)){
            $accounts->where('id', $accountId);
        }
        foreach ($accounts->get() as $account){
            if(!$account->isClosed($this->calendar_year)){
                $balances = $account->closingBalance($periodEnd);
                if(array_sum($balances) <> 0 ){
                    foreach($this->transactionCurrencies($account->id) as $currency){
                        $balances = $account->closingBalance($periodEnd, $currency->currency_id);
                        $localBalance = $balances[$reportingCurrency];
                        $foreignBalance = $balances[$currency->currency_id] * $rates[$currency->currency_id];

                        if($localBalance <> round($foreignBalance, config('ifrs.forex_scale'))) {
                            $transactions[] = $this->balanceAccount(
                                $forexAccountId,
                                $vatId,
                                $account, 
                                $localBalance, 
                                $foreignBalance, 
                                $periodEnd,
                                $currency
                            );
                        }
                    }
                }
            }
        }   
        return $transactions;
    }
}
