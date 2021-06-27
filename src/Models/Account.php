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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingAccountType;
use IFRS\Exceptions\HangingTransactions;
use IFRS\Exceptions\InvalidCategoryType;

/**
 * Class Account
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Category $category
 * @property Currency $currency
 * @property int|null $code
 * @property string $name
 * @property string $description
 * @property string $account_type
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 * @property float $openingBalance
 * @property float $currentBalance
 * @property float $closingBalance
 */
class Account extends Model implements Recyclable, Segregatable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * Account Type.
     *
     * @var string
     */

    const NON_CURRENT_ASSET = 'NON_CURRENT_ASSET';
    const CONTRA_ASSET = 'CONTRA_ASSET';
    const INVENTORY = 'INVENTORY';
    const BANK = 'BANK';
    const CURRENT_ASSET = 'CURRENT_ASSET';
    const RECEIVABLE = 'RECEIVABLE';
    const NON_CURRENT_LIABILITY = 'NON_CURRENT_LIABILITY';
    const CONTROL = 'CONTROL';
    const CURRENT_LIABILITY = 'CURRENT_LIABILITY';
    const PAYABLE = 'PAYABLE';
    const EQUITY = 'EQUITY';
    const OPERATING_REVENUE = 'OPERATING_REVENUE';
    const OPERATING_EXPENSE = 'OPERATING_EXPENSE';
    const NON_OPERATING_REVENUE = 'NON_OPERATING_REVENUE';
    const DIRECT_EXPENSE = 'DIRECT_EXPENSE';
    const OVERHEAD_EXPENSE = 'OVERHEAD_EXPENSE';
    const OTHER_EXPENSE = 'OTHER_EXPENSE';
    const RECONCILIATION = 'RECONCILIATION';

    /**
     * Purchaseable Account Types
     *
     * @var array
     */

    const PURCHASABLES = [
        Account::OPERATING_EXPENSE,
        Account::DIRECT_EXPENSE,
        Account::OVERHEAD_EXPENSE,
        Account::OTHER_EXPENSE,
        Account::NON_CURRENT_ASSET,
        Account::CURRENT_ASSET,
        Account::INVENTORY
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'account_type',
        'account_id',
        'currency_id',
        'category_id',
        'entity_id',
        'description',
        'code',
    ];

    /**
     * Account Closing Transactions Query
     *
     * @param int $year
     *
     * @return Builder
     */
    private function closingTransactionsQuery(int $year)
    {
        $transactionsTable = config('ifrs.table_prefix') . 'transactions';
        $closingTransactionsTable = config('ifrs.table_prefix') . 'closing_transactions';
        
        return DB::table($transactionsTable)
            ->join($closingTransactionsTable, $closingTransactionsTable . '.transaction_id', '=', $transactionsTable . '.id')
            ->where($transactionsTable . '.account_id', '=', $this->id)
            ->whereYear($transactionsTable . '.transaction_date', '=', $year)
            ->selectRaw('transaction_id AS id, account_id, ' . $transactionsTable . '.currency_id, reporting_period_id, credited, transaction_type, transaction_date, narration');
    }

    /**
     * Process the Transactions returned by a query.
     *
     * @param QueryBuilder $query
     *
     * @return array
     */
    private function processTransactions($query): array
    {
        $transactions = ['total' => 0, 'transactions' => []];

        foreach ($query->get() as $transaction) {
            $transaction->amount = abs(Ledger::contribution($this, $transaction->id));
            $transaction->contribution = $transaction->credited ? $transaction->amount * -1 : $transaction->amount;
            $transaction->type = Transaction::getType($transaction->transaction_type);
            $transaction->date = Carbon::parse($transaction->transaction_date)->toFormattedDateString();
            $transactions['transactions'][] = $transaction;
            $transactions['total'] += $transaction->contribution;
        }
        return $transactions;
    }

    /**
     * Get Human Readable Account Type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getType($type)
    {
        return config('ifrs')['accounts'][$type];
    }

    /**
     * Get Human Readable Account types
     *
     * @param array $types
     *
     * @return array
     */
    public static function getTypes($types)
    {
        $typeNames = [];

        foreach ($types as $type) {
            $typeNames[] = Account::getType($type);
        }
        return $typeNames;
    }

    /**
     * Get all accounts with opening balances for the given year
     *
     * @param int $year
     *
     * @return array
     */
    public static function openingBalances(int $year,$entity_id = null)
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }

        $accounts = collect([]);
        $balances = ['debit' => 0, 'credit' => 0];

        foreach (Account::all() as $account) {
            $account->openingBalance = $account->openingBalance($year)[$entity->currency_id];
            if ($account->openingBalance != 0) {
                $accounts->push($account);
            }
            if ($account->openingBalance > 0) {
                $balances['debit'] += $account->openingBalance;
            } else {
                $balances['credit'] += $account->openingBalance;
            }
        }
        return ['balances' => $balances, 'accounts' => $accounts];
    }

    /**
     * Chart of Account Section Balances for the Reporting Period.
     *
     * @param string $accountType
     * @param string | Carbon $startDate
     * @param string | Carbon $endDate
     *
     * @return array
     */
    public static function sectionBalances(
        array $accountTypes,
        $startDate = null,
        $endDate = null,
        $fullBalance = true,
        $entity_id = null
    ): array {

        if(is_null($entity_id)){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }

        $balances = ['sectionOpeningBalance' => 0, 'sectionClosingBalance' => 0, 'sectionMovement' => 0, 'sectionCategories' => []];

        $startDate = is_null($startDate) ? ReportingPeriod::periodStart($endDate,$entity) : Carbon::parse($startDate);
        $endDate = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);
        $periodStart = ReportingPeriod::periodStart($endDate,$entity);

        $year = ReportingPeriod::year($endDate,$entity);

        foreach (Account::whereIn('account_type', $accountTypes)->where('entity_id','=',$entity->id)->get() as $account) {
            
            $reportingCurrencyId = $entity->currency_id;

            $account->openingBalance = $account->openingBalance($year,null,$entity_id)[$reportingCurrencyId] + $account->currentBalance($periodStart, $startDate,null,$entity_id)[$reportingCurrencyId];
            $account->balanceMovement = $account->currentBalance($startDate, $endDate,null,$entity_id)[$reportingCurrencyId];
            
            $account->closingBalance = $fullBalance ? $account->openingBalance + $account->balanceMovement : $account->balanceMovement;

            $account->balanceMovement *= -1;

            if ($account->closingBalance <> 0 || $account->balanceMovement <> 0) {

                if (is_null($account->category)) {
                    $categoryName =  config('ifrs')['accounts'][$account->account_type];
                    $categoryId = 0;
                } else {
                    $category = $account->category;
                    $categoryName =  $category->name;
                    $categoryId = $category->id;
                }

                if (array_key_exists($categoryName, $balances['sectionCategories'])) {
                    $balances['sectionCategories'][$categoryName]['accounts']->push((object) $account->attributes);
                    $balances['sectionCategories'][$categoryName]['total'] += $account->closingBalance;
                } else {
                    $balances['sectionCategories'][$categoryName]['accounts'] = collect([(object) $account->attributes]);
                    $balances['sectionCategories'][$categoryName]['total'] = $account->closingBalance;
                    $balances['sectionCategories'][$categoryName]['id'] = $categoryId;
                }
                $balances['sectionOpeningBalance'] += $account->openingBalance;
                $balances['sectionMovement'] += $account->balanceMovement;
                $balances['sectionClosingBalance'] += $account->closingBalance;
            }
        }

        return $balances;
    }

    /**
     * Instance Type.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Account::getType($this->account_type);
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        return $type ? $this->type . ': ' . $this->name : $this->name;
    }

    /**
     * Account Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Account Category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Account Balances.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balances()
    {
        return $this->hasMany(Balance::class);
    }

    /**
     * Account attributes.
     *
     * @return object
     */
    public function attributes()
    {
        $this->attributes['closingBalance'] = $this->closingBalance();
        return (object) $this->attributes;
    }

    /**
     * Check if the account has closing transactions.
     * 
     * @param int $year
     * 
     */
    public function isClosed(int $year = null,$entity_id = null): bool
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }

        if(is_null($year)){
            $year = $entity->current_reporting_period->calendar_year;
        }   
        return $this->closingTransactionsQuery($year)->count() > 0;
    }

    /**
     * Get the account's has closing transactions.
     * 
     * @param int $year
     * 
     */
    public function closingTransactions(int $year = null,$entity_id = null): array
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }

        if(is_null($year)){
            $year = $entity->current_reporting_period->calendar_year;
        }  
        return $this->processTransactions($this->closingTransactionsQuery($year));
    }

    /**
     * Get Account's Opening Balances for the Reporting Period.
     *
     * @param int $year
     * @param int $currencyId
     *
     * @return array
     */
    public function openingBalance(int $year = null, int $currencyId = null,$entity_id = null): array
    {
        if(Auth::user()){
          $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }
        
        $balances = [$entity->currency_id => 0];

        if (!is_null($year)) {
            $period = ReportingPeriod::getPeriod($year."-01-01",$entity);
        } else {
            $period = $entity->current_reporting_period;
        }

        $openingBalances = $this->balances->filter(function ($balance, $key) use ($period) {
            return $balance->reporting_period_id == $period->id;
        });

        if (!is_null($currencyId)) {
            $openingBalances = $this->balances->filter(function ($balance, $key) use ($currencyId) {
                return $balance->currency_id == $currencyId;
            });
            $balances[$currencyId] = 0;
        }

        foreach ($openingBalances as $each) {
            
            if (!is_null($currencyId) && $entity->currency_id != $currencyId) {
                $each->balance_type == Balance::DEBIT ? 
                    $balances[$currencyId] += $each->balance / $each->exchangeRate->rate : 
                    $balances[$currencyId] -= $each->balance / $each->exchangeRate->rate;
            }
            $each->balance_type == Balance::DEBIT ? 
                $balances[$entity->currency_id] += $each->balance : 
                $balances[$entity->currency_id] -= $each->balance;
        }
        return $balances;
    }

    /**
     * Get Account's Current Balances for the Period given.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $currencyId
     *
     * @return array
     */
    public function currentBalance(Carbon $startDate = null, Carbon $endDate = null, int $currencyId = null,$entity_id = null): array
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }

        $startDate = is_null($startDate) ? ReportingPeriod::periodStart($endDate,$entity) : $startDate;
        $endDate = is_null($endDate) ? Carbon::now() : $endDate;
        return Ledger::balance($this, $startDate, $endDate, $currencyId,$entity);
    }

    /**
     * Get Account's Closing Balances for the Reporting Period.
     *
     * @param string $endDate
     * @param int $currencyId
     *
     * @return array
     */
    public function closingBalance(string $endDate = null, int $currencyId = null,$entity_id = null): array
    {

        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$entity_id)->first();
        }


        $endDate = is_null($endDate) ? ReportingPeriod::periodEnd(null,$entity) : Carbon::parse($endDate);
        $startDate = ReportingPeriod::periodStart($endDate,$entity);
        $year = ReportingPeriod::year($endDate,$entity);
        $balances =  $this->openingBalance($year, $currencyId,$entity_id);
        $transactions = $this->currentBalance($startDate, $endDate, $currencyId,$entity_id);
        foreach(array_keys($balances) as $currency){
            $balances[$currency] += $transactions[$currency];
        }
        return $balances;
    }

    /**
     * Account Transactions Query
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $currencyId
     *
     * @return Builder
     */
    public function transactionsQuery(Carbon $startDate, Carbon $endDate, int $currencyId = null)
    {
        $transactionsTable = config('ifrs.table_prefix') . 'transactions';
        $ledgerTable = config('ifrs.table_prefix') . 'ledgers';

        $query = DB::table(
            $transactionsTable
        )
            ->leftJoin($ledgerTable, $transactionsTable . '.id', '=', $ledgerTable . '.transaction_id')
            ->where($transactionsTable . '.deleted_at', null)
            ->where($transactionsTable . '.entity_id', $this->entity_id)
            ->where($transactionsTable . '.transaction_date', '>=', $startDate)
            ->where($transactionsTable . '.transaction_date', '<=', $endDate->endOfDay())
            ->select(
                $transactionsTable . '.id',
                $transactionsTable . '.transaction_date',
                $transactionsTable . '.transaction_no',
                $transactionsTable . '.reference',
                $transactionsTable . '.transaction_type',
                $transactionsTable . '.credited',
                $transactionsTable . '.narration',
                $ledgerTable . '.rate'
            )->distinct();

            if (!is_null($currencyId)) {
                $query->where($transactionsTable . '.currency_id', $currencyId);
            }
        $query->where(
            function ($query) use ($ledgerTable) {
                $query->where($ledgerTable . '.post_account', $this->id)
                    ->orwhere($ledgerTable . '.folio_account', $this->id);
            }
        );

        return $query;
    }

    /**
     * Get Account's Transactions for the Reporting Period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return array
     */
    public function getTransactions(string $startDate = null, string $endDate = null): array
    {

        $transactions = ['total' => 0, 'transactions' => []];
        $startDate = is_null($startDate) ? ReportingPeriod::periodStart($endDate) : Carbon::parse($startDate);
        $endDate = is_null($endDate) ? Carbon::now() : Carbon::parse($endDate);

        return $this->processTransactions($this->transactionsQuery($startDate, $endDate));
    }

    /**
     * Validate Account.
     */
    public function save(array $options = []): bool
    {

        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$this->entity_id)->first();
        }

        if (!isset($this->currency_id)) {
            $this->currency_id = $entity->currency_id;
        }

        if (is_null($this->account_type)) {
            throw new MissingAccountType();
        }

        $typeChanged = $this->isDirty('account_type') && $this->account_type != $this->getOriginal('account_type') && !is_null($this->id);
        
        if (is_null($this->code) ||  $typeChanged) {
            $this->code = $this->getAccountCode();
        }

        if (!is_null($this->category) && $this->category->category_type != $this->account_type) {
            throw new InvalidCategoryType($this->account_type, $this->category->category_type);
        }

        $this->name = ucfirst($this->name);
        return parent::save($options);
    }

    /**
     * Calculate Account Code.
     */
    private function getAccountCode(): int
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$this->entity_id)->first();
        }

        if (!isset($this->currency_id) && $entity) {
            $this->currency_id = $entity->currency_id;
        }

        $query = Account::withTrashed()
        ->where('account_type', $this->account_type);

        if(!is_null($entity)){
            $query->withoutGlobalScopes()->where('entity_id', $entity->id);
        }
        return config('ifrs')['account_codes'][$this->account_type] + $query->count() + 1;
    }

    /**
     * Check for Current Year Transactions.
     */
    public function delete(): bool
    {
        if ($this->closingBalance()[Auth::user()->entity->currency_id] != 0) {
            throw new HangingTransactions();
        }

        return parent::delete();
    }
}
