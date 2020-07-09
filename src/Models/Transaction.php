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

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Interfaces\Segragatable;
use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Assignable;
use IFRS\Interfaces\Clearable;

use IFRS\Traits\Assigning;
use IFRS\Traits\Clearing;
use IFRS\Traits\Recycling;
use IFRS\Traits\Segragating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingLineItem;
use IFRS\Exceptions\RedundantTransaction;
use IFRS\Exceptions\PostedTransaction;
use IFRS\Exceptions\HangingClearances;
use IFRS\Exceptions\ClosedReportingPeriod;
use IFRS\Exceptions\AdjustingReportingPeriod;

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
class Transaction extends Model implements Segragatable, Recyclable, Clearable, Assignable
{
    use Segragating;
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
        'transaction_no'
    ];

    /**
     * Construct new Transaction.
     */
    public function __construct($attributes = [])
    {
        $entity = Auth::user()->entity;
        $this->table = config('ifrs.table_prefix') . 'transactions';

        if (!isset($attributes['currency_id'])) {
            $attributes['currency_id'] = $entity->currency_id;
        }

        if (!isset($attributes['exchange_rate_id'])) {
            $attributes['exchange_rate_id'] = $entity->default_rate->id;
        }
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

    private $assignments = [];

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
        $classmap = [
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
        ];
        return $classmap[$type];
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
    public static function transactionNo(string $type, Carbon $transaction_date = null)
    {
        $period_count = ReportingPeriod::getPeriod($transaction_date)->period_count;
        $period_start = ReportingPeriod::periodStart($transaction_date);

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
     * Instance Type Translator.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Transaction::getType($this->transaction_type);
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
            foreach ($this->ledgers->where("entry_type", Balance::DEBIT) as $ledger) {
                $amount += $ledger->amount / $this->exchangeRate->rate;
            }
        } else {
            foreach ($this->getLineItems() as $lineItem) {
                $amount += $lineItem->amount * $lineItem->quantity;
                $amount += $lineItem->amount * ($lineItem->vat->rate / 100) * $lineItem->quantity;
            }
        }
        return $amount;
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
            $this->addLineItem($lineItem);
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
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction("add LineItem to");
        }

        if ($lineItem->account->id == $this->account->id) {
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
     * Relate LineItems to Transaction.
     */
    public function save(array $options = []): bool
    {
        $period = ReportingPeriod::getPeriod(Carbon::parse($this->transaction_date));

        if ($period->status == ReportingPeriod::CLOSED) {
            throw new ClosedReportingPeriod($period->calendar_year);
        }

        if ($period->status == ReportingPeriod::ADJUSTING && $this->transaction_type != Transaction::JN) {
            throw new AdjustingReportingPeriod();
        }

        $this->transaction_no = Transaction::transactionNo(
            $this->transaction_type,
            Carbon::parse($this->transaction_date)
        );

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

        Ledger::post($this);
    }

    /**
     * Check Transaction Relationships.
     */
    public function delete(): bool
    {
        // No hanging assignments

        if (count(Assignment::where("transaction_id", $this->id)->get()) > 0) {
            throw new HangingClearances();
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
