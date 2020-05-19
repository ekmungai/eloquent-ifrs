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


use IFRS\Reports\AccountSchedule;

use IFRS\Interfaces\Segragatable;

use IFRS\Traits\Segragating;

use IFRS\Exceptions\InsufficientBalance;
use IFRS\Exceptions\OverClearance;
use IFRS\Exceptions\SelfClearance;
use IFRS\Exceptions\UnassignableTransaction;
use IFRS\Exceptions\UnclearableTransaction;
use IFRS\Exceptions\UnpostedAssignment;
use IFRS\Exceptions\InvalidClearanceAccount;
use IFRS\Exceptions\InvalidClearanceCurrency;
use IFRS\Exceptions\InvalidClearanceEntry;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Interfaces\Assignable;

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
class Assignment extends Model implements Segragatable
{
    use Segragating;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'cleared_id',
        'cleared_type',
        'amount',
    ];

    /**
     * Bulk assign a transaction to outstanding Transactions, under FIFO (First in first out) methodology
     *
     * @param Assignable $transaction
     */

    public static function bulkAssign(Assignable $transaction): void {

        $balance = $transaction->balance();

        $schedule = new AccountSchedule($transaction->account->id, $transaction->currency->id);
        $schedule->getTransactions();

        foreach ($schedule->transactions as $outstanding){
            $unclearedAmount = $outstanding->originalAmount - $outstanding->clearedAmount;
            $cleared = Transaction::find($outstanding->id);

            if ($unclearedAmount > $balance) {
                $assignment = new Assignment(
                    [
                        'transaction_id' => $transaction->id,
                        'cleared_id' => $cleared->id,
                        'cleared_type' => $cleared->getClearedType(),
                        'amount' => $balance,
                    ]
                );
                $assignment->save();
                break;
            }else{
                $assignment = new Assignment(
                    [
                        'transaction_id' => $transaction->id,
                        'cleared_id' => $cleared->id,
                        'cleared_type' => $cleared->getClearedType(),
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
    private function validate() : void
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

        if ($this->cleared->getAmount() - $this->cleared->clearedAmount() < $this->amount) {
            throw new OverClearance($cleared_type, $this->amount);
        }
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
     */
    public function save(array $options = []) : bool
    {
        $this->validate();

        $transactionType = $this->transaction->transaction_type;
        $cleared_type = $this->cleared->transaction_type;

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
