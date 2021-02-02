<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Reports\IncomeStatement;

use IFRS\Interfaces\Clearable;
use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Clearing;
use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\InvalidBalanceType;
use IFRS\Exceptions\InvalidBalanceDate;
use IFRS\Exceptions\InvalidBalanceTransaction;
use IFRS\Exceptions\InvalidAccountClassBalance;

/**
 * Class Balance
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Account $account
 * @property Currency $currency
 * @property ExchangeRate $exchangeRate
 * @property integer $year
 * @property string $reference
 * @property string $transaction_no
 * @property string $transaction_type
 * @property string $balance_type
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Balance extends Model implements Recyclable, Clearable, Segregatable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use Clearing;
    use ModelTablePrefix;

    /**
     * Balance Model Name
     *
     * @var string
     */

    const MODELNAME = self::class;

    /**
     * Balance Type
     *
     * @var string
     */

    const DEBIT = "D";
    const CREDIT = "C";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency_id',
        'exchange_rate_id',
        'account_id',
        'reporting_period_id',
        'transaction_no',
        'reference',
        'balance_type',
        'entity_id',
        'transaction_type',
        'transaction_date',
        'amount'
    ];

    /**
     * Construct new Balance.
     */
    public function __construct($attributes = [])
    {
        $entity = Auth::user()->entity;

        if (!is_null($entity)) {
            $reportingPeriod = $entity->current_reporting_period;

            if (!isset($attributes['currency_id'])) {
                $attributes['currency_id'] = $entity->currency_id;
            }

            if (!isset($attributes['reporting_period_id'])) {
                $attributes['reporting_period_id'] = $reportingPeriod->id;
            }

            if (!isset($attributes['exchange_rate_id'])) {
                $attributes['exchange_rate_id'] = $entity->default_rate->id;
            }

            if (!isset($attributes['transaction_no']) && isset($attributes['currency_id']) && isset($attributes['account_id'])) {
                $currency = Currency::find($attributes['currency_id'])->currency_code;
                $attributes['transaction_no'] = $attributes['account_id'] . $currency . $reportingPeriod->calendar_year;
            }
        }

        if (!isset($attributes['transaction_type'])) {
            $attributes['transaction_type'] = Transaction::JN;
        }

        if (!isset($attributes['balance_type'])) {
            $attributes['balance_type'] = Balance::DEBIT;
        }

        return parent::__construct($attributes);
    }

    /**
     * Get Human Readable Balance Type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getType($type)
    {
        return config('ifrs')['balances'][$type];
    }

    /**
     * Get Human Readable Balance types
     *
     * @param array $types
     *
     * @return array
     */
    public static function getTypes($types)
    {
        $typeNames = [];

        foreach ($types as $type) {
            $typeNames[] = Balance::getType($type);
        }
        return $typeNames;
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        $description = $this->account->toString() . ' for year ' . $this->reportingPeriod->calendar_year;
        return $type ? $this->type . ' ' . array_pop($classname) . ': ' . $description : $description;
    }

    /**
     * Instance Type Translator.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Balance::getType($this->balance_type);
    }

    /**
     * Instance Transaction Type Translator.
     *
     * @return string
     */
    public function getTTypeAttribute()
    {
        return Transaction::getType($this->transaction_type);
    }

    /**
     * is_posted analog for Assignment model.
     */
    public function getIsPostedAttribute(): bool
    {
        return $this->exists();
    }

    /**
     * is_credited analog for Assignment model.
     *
     * @return bool
     */
    public function getIsCreditedAttribute(): bool
    {
        return $this->balance_type == Balance::CREDIT;
    }

    /**
     * cleared_type analog for Assignment model.
     *
     * @return string
     */
    public function getClearedTypeAttribute(): string
    {
        return Balance::MODELNAME;
    }

    /**
     * getAmount() analog for Assignment model.
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Balance Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Balance Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Balance Exchange Rate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    /**
     * Balance Reporting Period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reportingPeriod()
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    /**
     * Balance attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Balance Validation.
     */
    public function save(array $options = []): bool
    {

        if ($this->amount < 0) {
            throw new NegativeAmount("Balance");
        }

        if (!in_array($this->transaction_type, Assignment::CLEARABLES)) {
            throw new InvalidBalanceTransaction(Assignment::CLEARABLES);
        }

        if (!in_array($this->balance_type, [Balance::DEBIT, Balance::CREDIT])) {
            throw new InvalidBalanceType([Balance::DEBIT, Balance::CREDIT]);
        }

        if (in_array($this->account->account_type, IncomeStatement::getAccountTypes())) {
            throw new InvalidAccountClassBalance();
        }

        $entity = Auth::user()->entity;

        if (ReportingPeriod::periodStart()->lt($this->transaction_date) && !$entity->mid_year_balances) {
            throw new InvalidBalanceDate();
        }

        return parent::save();
    }
}
