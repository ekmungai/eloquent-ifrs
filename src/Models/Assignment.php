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
use IFRS\Exceptions\InsufficientBalance;
use IFRS\Exceptions\InvalidClearanceAccount;
use IFRS\Exceptions\InvalidClearanceCurrency;
use IFRS\Exceptions\InvalidClearanceEntry;
use IFRS\Exceptions\MissingForexAccount;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\OverClearance;
use IFRS\Exceptions\SelfClearance;
use IFRS\Exceptions\UnassignableTransaction;
use IFRS\Exceptions\UnclearableTransaction;
use IFRS\Exceptions\UnpostedAssignment;

use IFRS\Interfaces\Assignable;
use IFRS\Interfaces\Segregatable;

use IFRS\Reports\AccountSchedule;

use IFRS\Traits\ModelTablePrefix;
use IFRS\Traits\Segregating;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Assignment
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Assignable $transaction
 * @property Clearable $cleared
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Assignment extends Model implements Segregatable
{
    use Segregating;
    use SoftDeletes;
    use ModelTablePrefix;

    /**
     * Clearable Transaction Types
     *
     * @var array
     */

    const CLEARABLES = [
        Transaction::IN,
        Transaction::BL,
        Transaction::JN
    ];

    /**
     * Assignable Transaction Types
     *
     * @var array
     */

    const ASSIGNABLES = [
        Transaction::RC,
        Transaction::PY,
        Transaction::CN,
        Transaction::DN,
        Transaction::JN
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'assignment_date',
        'transaction_id',
        'cleared_id',
        'cleared_type',
        'forex_account_id',
        'amount',
    ];

    /**
     * Bulk assign a transaction to outstanding Transactions, under FIFO (First in first out) methodology
     *
     * @param Assignable $transaction
     */

    public static function bulkAssign(Assignable $transaction): void
    {

        $balance = $transaction->balance;

        $schedule = new AccountSchedule($transaction->account->id, $transaction->currency->id);
        $schedule->getTransactions();

        foreach ($schedule->transactions as $outstanding) {
            $unclearedAmount = $outstanding->originalAmount - $outstanding->clearedAmount;
            $cleared = Transaction::find($outstanding->id);

            if ($unclearedAmount > $balance) {
                $assignment = new Assignment(
                    [
                        'assignment_date' => Carbon::now(),
                        'transaction_id' => $transaction->id,
                        'cleared_id' => $cleared->id,
                        'cleared_type' => $cleared->cleared_type,
                        'amount' => $balance,
                    ]
                );
                $assignment->save();
                break;
            } else {
                $assignment = new Assignment(
                    [
                        'assignment_date' => Carbon::now(),
                        'transaction_id' => $transaction->id,
                        'cleared_id' => $cleared->id,
                        'cleared_type' => $cleared->cleared_type,
                        'amount' => $unclearedAmount,
                    ]
                );
                $assignment->save();
                $balance -= $unclearedAmount;
            }
        }
    }

    /**
     * Assignment Validation.
     */
    private function validate(): void
    {
        $transactionType = $this->transaction->transaction_type;
        $clearedType = $this->cleared->transaction_type;

        $transactionRate = $this->transaction->exchangeRate->rate;
        $clearedRate = $this->cleared->exchangeRate->rate;

        // Assignable Transactions
        $assignable = [
            Transaction::RC,
            Transaction::CN,
            Transaction::PY,
            Transaction::DN,
            Transaction::JN
        ];

        if (!in_array($transactionType, $assignable)) {
            throw new UnassignableTransaction($transactionType, $assignable);
        }

        // Clearable Transactions

        if (!in_array($clearedType, Assignment::CLEARABLES)) {
            throw new UnclearableTransaction($clearedType, Assignment::CLEARABLES);
        }

        if ($this->amount < 0) {
            throw new NegativeAmount("Assignment");
        }

        if ($this->cleared->id == $this->transaction->id && $this->cleared_type == Transaction::MODELNAME) {
            throw new SelfClearance();
        }

        if (!$this->transaction->is_posted || !$this->cleared->is_posted) {
            throw new UnpostedAssignment();
        }

        if ($this->cleared->account_id != $this->transaction->account_id) {
            throw new InvalidClearanceAccount();
        }

        if ($this->cleared->currency_id != $this->transaction->currency_id) {
            throw new InvalidClearanceCurrency();
        }

        if ($this->cleared->is_credited == $this->transaction->is_credited) {
            throw new InvalidClearanceEntry();
        }

        if ($this->transaction->balance < $this->amount) {
            throw new InsufficientBalance($transactionType, $this->amount, $clearedType);
        }

        if ($this->cleared->amount - $this->cleared->cleared_amount < $this->amount) {
            throw new OverClearance($clearedType, $this->amount);
        }

        if ($transactionRate !== $clearedRate && is_null($this->forexAccount)) {
            throw new MissingForexAccount();
        }
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        $description = $this->transaction->toString() . ' on ' . $this->assignment_date;
        return $type ? array_pop($classname) . ': ' . $description : $description;
    }

    /**
     * Transaction to be cleared.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Transaction|Balance to be cleared.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function cleared()
    {
        return $this->morphTo();
    }

    /**
     * Account for posting Exchange Rate Differences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function forexAccount()
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Assignment attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Assignment Validation.
     */
    public function save(array $options = []): bool
    {
        $this->validate();

        return parent::save();
    }
}
