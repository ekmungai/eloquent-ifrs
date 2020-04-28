<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Reports\IncomeStatement;

use Ekmungai\IFRS\Interfaces\Recyclable;
use Ekmungai\IFRS\Interfaces\Clearable;
use Ekmungai\IFRS\Interfaces\Segragatable;

use Ekmungai\IFRS\Traits\Recycling;
use Ekmungai\IFRS\Traits\Segragating;
use Ekmungai\IFRS\Traits\Clearing;

use Ekmungai\IFRS\Exceptions\InvalidAccountClassBalance;
use Ekmungai\IFRS\Exceptions\InvalidBalanceTransaction;
use Ekmungai\IFRS\Exceptions\InvalidBalance;
use Ekmungai\IFRS\Exceptions\NegativeAmount;

/**
 * Class Balance
 *
 * @package Ekmungai\Laravel-IFRS
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

    /**
     * Balance Model Name
     *
     * @var string
     */

    const MODELNAME = "Ekmungai\IFRS\Models\Balance";

    /**
     * Balance Type
     *
     * @var string
     */

    const DEBIT = "D";
    const CREDIT = "C";

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
     * Construct new Balance.
     *
     * @param Account $account
     * @param string $year
     * @param string $transaction_no
     * @param float $amount
     * @param string $balance_type
     * @param string $transaction_type
     * @param Currency $currency
     * @param ExchangeRate $exchangeRate
     * @param string $reference
     *
     * @return Balance
     */
    public static function new(
        Account $account,
        string $year,
        string $transaction_no,
        float $amount,
        string $balance_type = Balance::DEBIT,
        string $transaction_type = Transaction::JN,
        Currency $currency = null,
        ExchangeRate $exchangeRate = null,
        string $reference = null
    ) : Balance {
        $entity = Auth::user()->entity;

        $balance = new Balance();

        $balance->currency_id = !is_null($currency)? $currency->id : Auth::user()->entity->currency_id;
        $balance->exchange_rate_id = !is_null($exchangeRate)? $exchangeRate->id : $entity->defaultRate()->id;
        $balance->account_id = $account->id;
        $balance->year  = Carbon::parse($year);
        $balance->transaction_no = $transaction_no;
        $balance->amount = $amount;
        $balance->balance_type = $balance_type;
        $balance->transaction_type = $transaction_type;
        $balance->reference = $reference;

        return $balance;
    }

    /**
     * getId analog for Assignment model.
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
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
     * getTransactionNo analog for Assignment model.
     *
     * @return string
     */
    public function getTransactionNo() : string
    {
        return $this->transaction_no;
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
     *
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
