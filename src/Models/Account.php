<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segragatable;

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingAccountType;
use IFRS\Exceptions\HangingTransactions;
use Carbon\Carbon;

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
class Account extends Model implements Recyclable, Segragatable
{
    use Segragating;
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
    const CONTROL_ACCOUNT = 'CONTROL_ACCOUNT';
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
        'code',
    ];

    /**
     * Construct new Account.
     */
    public function __construct($attributes = [])
    {
        if (!isset($attributes['currency_id']) && Auth::user()->entity) {
            $attributes['currency_id'] = Auth::user()->entity->currency_id;
        }

        return parent::__construct($attributes);
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
     * Chart of Account Section Balances for the Reporting Period.
     *
     * @param string $accountType
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     */
    public static function sectionBalances(
        string $accountType,
        string $startDate = null,
        string $endDate = null
        ) : array {
            $balances = ["sectionTotal" => 0, "sectionCategories" => []];

            $startDate = is_null($startDate)?ReportingPeriod::periodStart($endDate):Carbon::parse($startDate);
            $endDate = is_null($endDate)?Carbon::now():Carbon::parse($endDate);

            $year = ReportingPeriod::year($endDate);

            foreach (Account::where("account_type", $accountType)->get() as $account) {
                $account->openingBalance = $account->openingBalance($year);
                $account->currentBalance = Ledger::balance($account, $startDate, $endDate);
                $closingBalance = $account->openingBalance + $account->currentBalance;

                if ($closingBalance <> 0) {
                    $categoryName = is_null($account->category)? $account->account_type: $account->category->name;

                    if (in_array($categoryName, $balances["sectionCategories"])) {
                        $balances["sectionCategories"][$categoryName]['accounts']->push($account->attributes());
                        $balances["sectionCategories"][$categoryName]['total'] += $closingBalance;
                    } else {
                        $balances["sectionCategories"][$categoryName]['accounts'] = collect([$account->attributes()]);
                        $balances["sectionCategories"][$categoryName]['total'] = $closingBalance;
                    }
                }
                $balances["sectionTotal"] += $closingBalance;
            }

            return $balances;
    }

    /**
     * Instance Type.
     *
     * @return string
     */
    public function type()
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
        return $type? $this->type().' Account: '.$this->name : $this->name;
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
        $this->attributes['closingBalance'] = $this->closingBalance(date("Y-m-d"));
        return (object) $this->attributes;
    }

    /**
     * Get Account's Opening Balance for the Reporting Period.
     *
     * @param string $year
     *
     * @return float
     */
    public function openingBalance(string $year = null) : float
    {
        if (is_null($year)) {
            $year = ReportingPeriod::year();
        }

        $balance = 0;
        foreach ($this->balances->where("year", $year) as $record) {
            $amount = $record->amount/$record->exchangeRate->rate;
            $record->balance_type == Balance::DEBIT ? $balance += $amount : $balance -= $amount;
        }
        return $balance;
    }

    /**
     * Get Account's Closing Balance for the Reporting Period.
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return float
     */
    public function closingBalance(string $startDate = null, string $endDate = null) : float
    {
        $startDate = is_null($startDate)?ReportingPeriod::periodStart($endDate):Carbon::parse($startDate);
        $endDate = is_null($endDate)? Carbon::now():Carbon::parse($endDate);

        $year = ReportingPeriod::year($endDate);

        return $this->openingBalance($year) + Ledger::balance($this, $startDate, $endDate);
    }

    /**
     * Calculate Account Code.
     */
    public function save(array $options = []) : bool
    {
        if (is_null($this->code)) {
            if (is_null($this->account_type)) {
                throw new MissingAccountType();
            }

            $section = Arr::collapse(array_values(config('ifrs')))[$this->account_type];
            $this->code = $section + Account::withTrashed()
            ->where("account_type", $this->account_type)
            ->count() + 1;
        }

        $this->name = ucfirst($this->name);
        return parent::save($options);
    }

    /**
     * Check for Current Year Transactions.
     */
    public function delete(): bool
    {
        if ($this->closingBalance() != 0) {
            throw new HangingTransactions();
        }

        return parent::delete();
    }
}
