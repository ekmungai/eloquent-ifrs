<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Segragatable;
use Ekmungai\IFRS\Interfaces\Recyclable;
use Ekmungai\IFRS\Interfaces\Assignable;
use Ekmungai\IFRS\Interfaces\Clearable;

use Ekmungai\IFRS\Traits\Assigning;
use Ekmungai\IFRS\Traits\Clearing;
use Ekmungai\IFRS\Traits\Recycling;
use Ekmungai\IFRS\Traits\Segragating;

use Ekmungai\IFRS\Exceptions\MissingLineItem;
use Ekmungai\IFRS\Exceptions\RedundantTransaction;
use Ekmungai\IFRS\Exceptions\PostedTransaction;
use Ekmungai\IFRS\Exceptions\HangingClearances;

/**
 * Class Transaction
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property ExchangeRate $exchangeRate
 * @property Account $account
 * @property Currency $currency
 * @property Carbon $date
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

    /**
     * Balance Model Name
     *
     * @var array
     */

    const MODELNAME = "Ekmungai\IFRS\Models\Transaction";

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
     * Transaction Classes
     *
     * @var array
     */

    public static $transactionClasses = [
        self::CS => 'CashSale',
        self::IN => 'ClientInvoice',
        self::CN => 'CreditNote',
        self::RC => 'ClientReceipt',
        self::CP => 'CashPurchase',
        self::BL => 'SupplierBill',
        self::DN => 'DebitNote',
        self::PY => 'SupplierPayment',
        self::CE => 'ContraEntry',
        self::JN => 'JournalEntry'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency_id',
        'exchange_rate_id',
        'account_id',
        'date',
        'narration',
        'reference',
        'amount',
        'credited',
        'transaction_type',
        'transaction_no'
    ];

    /**
     * Transaction LineItems
     *
     * @var array $lineItems
     */

    private $lineItems = [];

    /**
     * Check if LineItem already exists.
     *
     * @param int $id
     *
     * @return int|false
     */
    private function lineItemExists(int $id = null)
    {
        return collect($this->lineItems)->search(function ($item, $key) use ($id) {
            return $item->id == $id;
        });
    }

    /**
     * Save LineItems.
     */
    private function saveLineItems() : void
    {
        if (count($this->lineItems)) {
            $lineItem = array_pop($this->lineItems);
            $this->savedLineItems()->save($lineItem);

            $this->saveLineItems();
        }
    }

    /**
     * Get Human Readable Transaction type
     *
     * @param string $type
     *
     * @return string
     */
    public static function getType($type) : string
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
    public static function getTypes($types) : array
    {
        $typeNames = [];

        foreach ($types as $type) {
            $typeNames[] = Transaction::getType($type);
        }
        return $typeNames;
    }

    /**
     * The next Transaction number for the transaction type and date.
     *
     * @param string $type
     * @param Carbon $date
     *
     * @return string
     */
    public static function transactionNo(string $type, Carbon $date = null)
    {
        $period_count = ReportingPeriod::periodCount($date);
        $period_start = ReportingPeriod::periodStart($date);

        $next_id =  Transaction::withTrashed()
        ->where("transaction_type", $type)
        ->where("date", ">=", $period_start)
        ->count() + 1;

        return $type.str_pad((string) $period_count, 2, "0", STR_PAD_LEFT)
        ."/".
        str_pad((string) $next_id, 4, "0", STR_PAD_LEFT);
    }

    public function __construct($attributes = []) {

        $entity = Auth::user()->entity;

        if (!isset($attributes['currency_id'])) {
            $attributes['currency_id'] = $entity->currency_id;
        }

        if (!isset($attributes['exchange_rate_id'])) {
            $attributes['exchange_rate_id'] = $entity->defaultRate()->id;
        }

        if (!isset($attributes['date'])) {
            $attributes['date'] = Carbon::now();
        }

        $attributes['amount'] = 0;

        return parent::__construct($attributes);
    }

    /**
     * getId analog for Assignment model.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * isPosted analog for Assignment model.
     */
    public function isPosted(): bool
    {
        return count($this->ledgers) > 0;
    }

    /**
     * isCredited analog for Assignment model.
     *
     * @return bool
     */
    public function isCredited() : bool
    {
        return boolval($this->credited);
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
     * getClearedType analog for Assignment model.
     *
     * @return string
     */
    public function getClearedType() : string
    {
        return Transaction::MODELNAME;
    }

    /**
     * Transaction Saved Line Items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function savedLineItems()
    {
        return $this->HasMany('Ekmungai\IFRS\Models\LineItem', 'transaction_id', 'id');
    }

    /**
     * Transaction Ledgers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers()
    {
        return $this->HasMany('Ekmungai\IFRS\Models\Ledger', 'transaction_id', 'id');
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
        return $this->HasMany('Ekmungai\IFRS\Models\Assignment', 'transaction_id', 'id');
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
        foreach ($this->savedLineItems as $lineItem) {
            $this->addLineItem($lineItem);
        }
        return $this->lineItems;
    }

    /**
     * Add LineItem to Transaction LineItems.
     *
     * @param LineItem $lineItem
     */
    public function addLineItem(LineItem $lineItem) : void
    {
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction();
        }

        if ($lineItem->account->id == $this->account->id) {
            throw new RedundantTransaction();
        }

        if ($this->lineItemExists($lineItem->id) === false) {
            $this->lineItems[] = $lineItem;
        }
    }

    /**
     * Remove LineItem from Transaction LineItems.
     *
     * @param LineItem $lineItem
     */
    public function removeLineItem(LineItem $lineItem) : void
    {
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction();
        }

        $key = $this->lineItemExists($lineItem->id);

        if ($key !== false) {
            unset($this->lineItems[$key]);
        }

        $lineItem->transaction()->dissociate();
        $lineItem->save();

        // reload items to reflect changes
        $this->load('savedLineItems');
    }

    /**
     * Relate LineItems to Transaction.
     */
    public function save(array $options = []): bool
    {
        $this->transaction_no = Transaction::transactionNo(
            $this->transaction_type,
            Carbon::parse($this->date)
        );

        $save = parent::save();
        $this->saveLineItems();

        // reload items to reflect changes
        $this->load('savedLineItems');

        return $save;
    }

    /**
     * Post Transaction to the Ledger.
     */
    public function post() : void
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
        if (count($this->assignments) > 0) {
            throw new HangingClearances();
        }

        // Remove clearance records
        $this->clearances->map(function ($clearance, $key) {
            $clearance->delete();
            return $clearance;
        });

        return parent::delete();
    }

    /**
     * Check Transaction Integrity.
     */
    public function checkIntegrity(): bool
    {
        // verify transaction ledger hashes
        return $this->ledgers->every(function ($ledger, $key) {
            return password_verify($ledger->hashed(), $ledger->hash);
        });
    }
}
