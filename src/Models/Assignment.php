<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Interfaces\Segragatable;
use App\Interfaces\Assignable;
use App\Interfaces\Clearable;

use App\Traits\Segragating;

use App\Exceptions\InsufficientBalance;
use App\Exceptions\OverClearance;
use App\Exceptions\SelfClearance;
use App\Exceptions\UnassignableTransaction;
use App\Exceptions\UnclearableTransaction;
use App\Exceptions\UnpostedAssignment;
use App\Exceptions\InvalidClearanceAccount;
use App\Exceptions\InvalidClearanceCurrency;
use App\Exceptions\InvalidClearanceEntry;
use App\Exceptions\NegativeAmount;

/**
 * Class Assignment
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Entity $entity
 * @property Assignable $transaction
 * @property Clearable $cleared
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Assignment extends Model implements Segragatable
{
    use Segragating;
    use SoftDeletes;

    /**
     * Construct new Assignment.
     *
     * @param Assignable $transaction
     * @param Clearable $cleared
     * @param float $amount
     *
     * @return Assignment
     */
    public static function new(Assignable $transaction, Clearable $cleared, float $amount) : Assignment
    {
        $assignment = new Assignment();

        $assignment->transaction_id = $transaction->getId();
        $assignment->cleared_id = $cleared->getId();
        $assignment->cleared_type = $cleared->getClearedType();

        $assignment->amount = $amount;

        return $assignment;
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
     *
     */
    public function save(array $options = []) : bool
    {
        $transactionType = $this->transaction->transaction_type;
        $cleared_type = $this->cleared->transaction_type;

        if ($this->amount < 0) {
            throw new NegativeAmount("Assignment");
        }

        if ($this->cleared->id == $this->transaction->id and $this->cleared_type == Transaction::MODELNAME) {
            throw new SelfClearance();
        }

        if (!$this->transaction->isPosted() || !$this->cleared->isPosted()) {
            throw new UnpostedAssignment();
        }

        if ($this->cleared->account_id != $this->transaction->account_id) {
            throw new InvalidClearanceAccount();
        }

        if ($this->cleared->currency_id != $this->transaction->currency_id) {
            throw new InvalidClearanceCurrency();
        }

        if ($this->cleared->isCredited() == $this->transaction->isCredited()) {
            throw new InvalidClearanceEntry();
        }

        if ($this->transaction->balance() < $this->amount) {
            throw new InsufficientBalance($transactionType, $this->amount, $cleared_type);
        }

        if ($this->cleared->amount - $this->cleared->clearedAmount() < $this->amount) {
            throw new OverClearance($cleared_type, $this->amount);
        }

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
        $clearable = [
            Transaction::IN,
            Transaction::BL,
            Transaction::JN
        ];

        if (!in_array($cleared_type, $clearable)) {
            throw new UnclearableTransaction($cleared_type, $clearable);
        }
        return parent::save();
    }
}
