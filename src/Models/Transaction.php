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

use IFRS\Interfaces\Clearable;
use IFRS\Interfaces\Assignable;
use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Clearing;
use IFRS\Traits\Assigning;
use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingLineItem;
use IFRS\Exceptions\HangingClearances;
use IFRS\Exceptions\PostedTransaction;
use IFRS\Exceptions\UnpostedAssignment;
use IFRS\Exceptions\RedundantTransaction;
use IFRS\Exceptions\ClosedReportingPeriod;
use IFRS\Exceptions\InvalidTransactionDate;
use IFRS\Exceptions\AdjustingReportingPeriod;
use IFRS\Exceptions\InvalidCurrency;

/**
 * Class Transaction
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property ExchangeRate $exchangeRate
 * @property Account $account
 * @property Currency $currency
 * @property Carbon $transaction_date
 * @property string $reference
 * @property string $transaction_no
 * @property string $transaction_type
 * @property string $narration
 * @property bool $credited
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Transaction extends Model implements Segregatable, Recyclable, Clearable, Assignable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use Clearing;
    use Assigning;
    use ModelTablePrefix;

    /**
     * Transaction Model Name
     *
     * @var string
     */

    const MODELNAME = self::class;

    /**
     * Transaction Types
     *
     * @var string
     */

    const CS = 'CS';
    const IN = 'IN';
    const CN = 'CN';
    const RC = 'RC';
    const CP = 'CP';
    const BL = 'BL';
    const DN = 'DN';
    const PY = 'PY';
    const CE = 'CE';
    const JN = 'JN';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency_id',
        'exchange_rate_id',
        'account_id',
        'transaction_date',
        'narration',
        'reference',
        'credited',
        'transaction_type',
        'transaction_no',
        'entity_id',
    ];

    protected $dates = [
        'transaction_date'
    ];

    /**
     * Construct new Transaction.
     */
    public function __construct($attributes = [])
    {
//       if(Auth::user()){
//           $entity = Auth::user()->entity;
//       }
//
        $this->table = config('ifrs.table_prefix') . 'transactions';
//
//        if (!isset($attributes['exchange_rate_id']) && !is_null($entity)) {
//            $attributes['exchange_rate_id'] = $entity->default_rate->id;
//        }
        $attributes['transaction_date'] = !isset($attributes['transaction_date']) ? Carbon::now() : Carbon::parse($attributes['transaction_date']);

        return parent::__construct($attributes);
    }

    /**
     * Transaction LineItems
     *
     * @var array $items
     */

    private $items = [];

    /**
     * Transactions to be cleared and their clearance amounts
     *
     * @var array $assignments
     */

    private $assigned = [];

    /**
     * Check if LineItem already exists.
     *
     * @param int $id
     *
     * @return int|false
     */
    private function lineItemExists(int $id = null)
    {
        return collect($this->items)->search(
            function ($item, $key) use ($id) {
                return $item->id == $id;
            }
        );
    }

    /**
     * Check if Assigned Transaction already exists.
     *
     * @param int $id
     *
     * @return int|false
     */
    private function assignedTransactionExists(int $id = null)
    {
        return collect($this->assigned)->search(
            function ($transaction, $key) use ($id) {
                return $transaction['id'] == $id;
            }
        );
    }

    /**
     * Check the balance remaining after clearing the currently Assigned Transactions.
     *
     * @return float
     */
    private function assignedAmountBalance()
    {
        $balance = $this->balance;
        foreach ($this->assigned as $assignedSoFar) {
            $balance -= $assignedSoFar['amount'];
        }

        return $balance;
    }

    /**
     * Save LineItems.
     */
    private function saveLineItems(): void
    {
        if (count($this->items)) {
            $lineItem = array_pop($this->items);
            $this->lineItems()->save($lineItem);

            $this->saveLineItems();
        }
    }

    /**
     * Get Transaction Class
     *
     * @param string $type
     *
     * @return string
     */
    public static function getClass($type): string
    {
        return [
            'CS' => 'CashSale',
            'IN' => 'ClientInvoice',
            'CN' => 'CreditNote',
            'RC' => 'ClientReceipt',
            'CP' => 'CashPurchase',
            'BL' => 'SupplierBill',
            'DN' => 'DebitNote',
            'PY' => 'SupplierPayment',
            'CE' => 'ContraEntry',
            'JN' => 'JournalEntry',
        ][$type];
    }

    /**
     * Get Human Readable Transaction type
     *
     * @param string $type
     *
     * @return string
     */
    public static function getType($type): string
    {
        return config('ifrs')['transactions'][$type];
    }

    /**
     * Get Human Readable Transaction types
     *
     * @param array $types
     *
     * @return array
     */
    public static function getTypes($types): array
    {
        $typeNames = [];

        foreach ($types as $type) {
            $typeNames[] = Transaction::getType($type);
        }
        return $typeNames;
    }

    /**
     * The next Transaction number for the transaction type and transaction_date.
     *
     * @param string $type
     * @param Carbon $transaction_date
     *
     * @return string
     */
    public static function transactionNo(string $type, Carbon $transaction_date = null,$entity = null)
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }

        $period_count = ReportingPeriod::getPeriod($transaction_date,$entity)->period_count;
        $period_start = ReportingPeriod::periodStart($transaction_date,$entity);

        $next_id =  Transaction::withTrashed()
            ->where("transaction_type", $type)
            ->where("transaction_date", ">=", $period_start)
            ->count() + 1;

        return $type . str_pad((string) $period_count, 2, "0", STR_PAD_LEFT)
            . "/" .
            str_pad((string) $next_id, 4, "0", STR_PAD_LEFT);
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $amount = ' for ' . number_format($this->amount, 2);
        return $type ? $this->type . ': ' . $this->transaction_no . $amount : $this->transaction_no . $amount;
    }

    /**
     * Transaction Saved Line Items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lineItems()
    {
        return $this->hasMany(LineItem::class, 'transaction_id', 'id');
    }

    /**
     * Transaction Ledgers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'transaction_id', 'id');
    }

    /**
     * Transaction Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Transaction Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Transaction Exchange Rate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    /**
     * Transaction Assignments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'transaction_id', 'id');
    }

    /**
     * Instance Type Translator.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Transaction::getType($this->transaction_type);
    }

    /**
     * is_posted analog for Assignment model.
     */
    public function getIsPostedAttribute(): bool
    {
        return count($this->ledgers) > 0;
    }

    /**
     * is_credited analog for Assignment model.
     *
     * @return bool
     */
    public function getIsCreditedAttribute(): bool
    {
        return boolval($this->credited);
    }

    /**
     * cleared_type analog for Assignment model.
     *
     * @return string
     */
    public function getClearedTypeAttribute(): string
    {
        return Transaction::MODELNAME;
    }

    /**
     * amount analog for Assignment model.
     *
     * @return float
     */
    public function getAmountAttribute(): float
    {
        $amount = 0;

        if ($this->is_posted) {

            $entry_type = $this->credited ? Balance::CREDIT : Balance::DEBIT;

            foreach (Ledger::where([
                "transaction_id" => $this->id,
                "entry_type" => $entry_type,
                "post_account" => $this->account_id,
                "currency_id" => $this->currency_id
            ])->get() as $ledger) {
                $amount += $ledger->amount / $this->exchangeRate->rate;
            }
        } else {
            foreach ($this->getLineItems() as $lineItem) {
                $amount += $lineItem->amount * $lineItem->quantity;
                if (!$lineItem->vat_inclusive) {
                    $amount += $lineItem->amount * ($lineItem->vat->rate / 100) * $lineItem->quantity;
                }
            }
        }
        return $amount;
    }

    /**
     * Total Vat amount of the transaction.
     *
     * @return array
     */
    public function getVatAttribute(): array
    {
        $vat = ['total' => 0];
        foreach ($this->getLineItems() as $lineItem) {
            if ($lineItem->vat->rate > 0) {
                $vatAmount = $lineItem->amount * ($lineItem->vat->rate / 100) * $lineItem->quantity;
                $vat['total'] += $vatAmount;
                if (array_key_exists($lineItem->vat->code, $vat)) {
                    $vat[$lineItem->vat->code] += $vatAmount;
                } else {
                    $vat[$lineItem->vat->code] = $vatAmount;
                }
            }
        }
        return $vat;
    }

    /**
     * Transaction is assignable predicate.
     *
     * @return bool
     */
    public function getAssignableAttribute(): bool
    {
        return count($this->clearances) == 0 && in_array($this->transaction_type, Assignment::ASSIGNABLES);
    }

    /**
     * Transaction is clearable predicate.
     *
     * @return bool
     */
    public function getClearableAttribute(): bool
    {
        return count($this->assignments) == 0 && in_array($this->transaction_type, Assignment::CLEARABLES);
    }

    /**
     * Transaction date.
     *
     * @return string
     */
    public function getDateAttribute(): string
    {
        return Carbon::parse($this->transaction_date)->toFormattedDateString();
    }

    /**
     * Transaction attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Get Transaction LineItems.
     *
     * @return array
     */
    public function getLineItems()
    {
        foreach ($this->lineItems as $lineItem) {
            if ($this->lineItemExists($lineItem->id) === false) {
                $this->items[] = $lineItem;
            }
        }
        return $this->items;
    }

    /**
     * Add LineItem to Transaction LineItems.
     *
     * @param LineItem $lineItem
     */
    public function addLineItem(LineItem $lineItem): void
    {
        if (in_array($lineItem->account->account_type, config('ifrs.single_currency')) && $lineItem->account->currency_id != $this->currency_id) {
            throw new InvalidCurrency("Transaction", $lineItem->account->account_type);
        }

        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction("add LineItem to");
        }

        if ($lineItem->account_id == $this->account_id) {
            throw new RedundantTransaction();
        }

        if ($this->lineItemExists($lineItem->id) === false) {
            $this->items[] = $lineItem;
        }
    }

    /**
     * Remove LineItem from Transaction LineItems.
     *
     * @param LineItem $lineItem
     */
    public function removeLineItem(LineItem $lineItem): void
    {
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction("remove LineItem from");
        }

        $key = $this->lineItemExists($lineItem->id);

        if ($key !== false) {
            unset($this->items[$key]);
        }

        $lineItem->transaction()->dissociate();
        $lineItem->save();

        // reload items to reflect changes
        $this->load('lineItems');
    }

    /**
     * Get this Transaction's assigned Transactions.
     *
     * @return array
     */
    public function getAssigned()
    {
        return $this->assigned;
    }

    /**
     * Add Transaction to this Transaction's assigned Transactions.
     *
     * @param array $toBeAssigned
     */
    public function addAssigned(array $toBeAssigned): void
    {
        if (!Transaction::find($toBeAssigned['id'])->is_posted) {
            throw new UnpostedAssignment();
        }

        $existing = $this->assignments->where('cleared_id', $toBeAssigned['id'])->first();
        if ($existing) {
            $existing->delete();
        }

        if ($this->assignedTransactionExists($toBeAssigned['id']) === false && $toBeAssigned['amount'] > 0) {
            if ($this->assignedAmountBalance() > $toBeAssigned['amount']) {
                $this->assigned[] = $toBeAssigned;
            } elseif ($this->assignedAmountBalance() > 0) {
                $this->assigned[] = [
                    'id' => $toBeAssigned['id'],
                    'amount' => $this->assignedAmountBalance()
                ];
            }
        }
    }

    /**
     * Create assignments for the assigned transactions being staged.
     */
    public function processAssigned(): void
    {
        foreach ($this->assigned as $outstanding) {
            $cleared = Transaction::find($outstanding['id']);

            $assignment = new Assignment([
                'assignment_date' => Carbon::now(),
                'transaction_id' => $this->id,
                'cleared_id' => $cleared->id,
                'cleared_type' => $cleared->cleared_type,
                'amount' => $outstanding['amount'],
            ]);
            $assignment->save();
        }
    }

    /**
     * Relate LineItems to Transaction.
     */
    public function save(array $options = []): bool
    {
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$this->entity_id)->first();
        }

        if(!isset($this->exchange_rate_id) && !is_null($entity)){
            $this->exchange_rate_id = $entity->default_rate->id;
        }

        $this->transaction_date = !isset($this->transaction_date) ? Carbon::now() : Carbon::parse($this->transaction_date);

        if(isset($this->exchange_rate_id) && !isset($this->currency_id)){
            $this->currency_id = $this->exchangeRate->currency_id;
        }

        $period = ReportingPeriod::getPeriod(Carbon::parse($this->transaction_date),$entity);

        if (ReportingPeriod::periodStart($this->transaction_date,$entity)->eq(Carbon::parse($this->transaction_date))) {
            throw new InvalidTransactionDate();
        }

        if ($period->status == ReportingPeriod::CLOSED) {
            throw new ClosedReportingPeriod($period->calendar_year);
        }

        if ($period->status == ReportingPeriod::ADJUSTING && $this->transaction_type != Transaction::JN) {
            throw new AdjustingReportingPeriod();
        }
        
        if (in_array($this->account->account_type, config('ifrs.single_currency')) && 
            $this->account->currency_id != $this->currency_id && 
            $this->currency_id != $entity->currency_id) {
            throw new InvalidCurrency("Transaction", $this->account->account_type);
        }

        if(!isset($this->currency_id)){
            $this->currency_id = $this->account->currency_id;
        }

        if (is_null($this->transaction_no)) {
            $this->transaction_no = Transaction::transactionNo(
                $this->transaction_type,
                Carbon::parse($this->transaction_date),
                $entity,
            );
        }

        $save = parent::save();
        $this->saveLineItems();

        // reload items to reflect changes
        $this->load('lineItems');

        return $save;
    }

    /**
     * Post Transaction to the Ledger.
     */
    public function post(): void
    {
        if (empty($this->getLineItems())) {
            throw new MissingLineItem();
        }
        $this->save();

        //pass the entity id if user not logged in
        if(Auth::user()){
            $entity = Auth::user()->entity;
        }else{
            $entity = Entity::where('id','=',$this->entity_id)->first();
        }

        Ledger::post($this,$entity);
    }

    /**
     * Check Transaction Relationships.
     */
    public function delete(): bool
    {
        // No hanging assignments
        if (count($this->assignments) > 0) {
            throw new HangingClearances();
        }
        
        // No deleting posted transactions
        if (count($this->ledgers) > 0) {
            throw new PostedTransaction('delete');
        }

        // Remove clearance records
        $this->clearances->map(
            function ($clearance, $key) {
                $clearance->delete();
                return $clearance;
            }
        );

        return parent::delete();
    }

    /**
     * Check Transaction Integrity.
     */
    public function getHasIntegrityAttribute(): bool
    {
        // verify transaction ledger hashes
        return $this->ledgers->every(
            function ($ledger, $key) {
                return password_verify($ledger->hashed(), $ledger->hash);
            }
        );
    }
}
