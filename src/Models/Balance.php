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

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Clearable;
use IFRS\Interfaces\Segragatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segragating;
use IFRS\Traits\Clearing;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\InvalidAccountClassBalance;
use IFRS\Exceptions\InvalidBalanceTransaction;
use IFRS\Exceptions\InvalidBalance;
use IFRS\Exceptions\NegativeAmount;

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
class Balance extends Model implements Recyclable, Clearable, Segragatable
{
    use Segragating;
    use SoftDeletes;
    use Recycling;
    use Clearing;
    use ModelTablePrefix;

    /**
     * Balance Model Name
     *
     * @var string
     */

    const MODELNAME = "IFRS\Models\Balance";

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
        'year',
        'transaction_no',
        'reference',
        'balance_type',
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

        if (!isset($attributes['currency_id'])) {
            $attributes['currency_id'] = $entity->currency_id;
        }

        if (!isset($attributes['exchange_rate_id'])) {
            $attributes['exchange_rate_id'] = $entity->defaultRate()->id;
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
     * isPosted analog for Assignment model.
     */
    public function isPosted() : bool
    {
        return $this->exists();
    }

    /**
     * isCredited analog for Assignment model.
     *
     * @return bool
     */
    public function isCredited() : bool
    {
        return $this->balance_type == Balance::CREDIT;
    }

    /**
     * getClearedType analog for Assignment model.
     *
     * @return string
     */
    public function getClearedType() : string
    {
        return Balance::MODELNAME;
    }

    /**
     * getAmount analog for Assignment model.
     *
     * @return string
     */
    public function getAmount() : string
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
    public function save(array $options = []) : bool
    {
        $transactionTypes = [
            Transaction::IN,
            Transaction::BL,
            Transaction::JN
        ];

        $accountTypes = array_merge(
            array_keys(config('ifrs')[IncomeStatement::OPERATING_REVENUES]),
            array_keys(config('ifrs')[IncomeStatement::NON_OPERATING_REVENUES]),
            array_keys(config('ifrs')[IncomeStatement::OPERATING_EXPENSES]),
            array_keys(config('ifrs')[IncomeStatement::NON_OPERATING_EXPENSES])
        );

        if ($this->amount < 0) {
            throw new NegativeAmount("Balance");
        }

        if (!in_array($this->transaction_type, $transactionTypes)) {
            throw new InvalidBalanceTransaction($transactionTypes);
        }

        if (!in_array($this->balance_type, [Balance::DEBIT, Balance::CREDIT])) {
            throw new InvalidBalance([Balance::DEBIT, Balance::CREDIT]);
        }

        if (in_array($this->account->account_type, $accountTypes)) {
            throw new InvalidAccountClassBalance();
        }

        return parent::save();
    }
}
