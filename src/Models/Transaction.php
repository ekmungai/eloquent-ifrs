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
     * Transaction Model Name
     *
     * @var array
     */

    const MODELNAME = "IFRS\Models\Transaction";

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
        'date',
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

        if (!isset($attributes['currency_id'])) {
            $attributes['currency_id'] = $entity->currency_id;
        }

        if (!isset($attributes['exchange_rate_id'])) {
            $attributes['exchange_rate_id'] = $entity->defaultRate()->id;
        }

        $attributes['date'] = !isset($attributes['date'])? Carbon::now(): Carbon::parse($attributes['date']);

        return parent::__construct($attributes);
    }

    /**
     * Transaction LineItems
     *
     * @var array $items
     */

    private $items = [];

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
    private function saveLineItems() : void
    {
        if (count($this->items)) {
            $lineItem = array_pop($this->items);
            $this->lineItems()->save($lineItem);

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

    /**
     * Transaction Saved Line Items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lineItems()
    {
        return $this->HasMany('IFRS\Models\LineItem', 'transaction_id', 'id');
    }

    /**
     * Transaction Ledgers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers()
    {
        return $this->HasMany('IFRS\Models\Ledger', 'transaction_id', 'id');
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
        return $this->HasMany('IFRS\Models\Assignment', 'transaction_id', 'id');
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
     * getClearedType analog for Assignment model.
     *
     * @return string
     */
    public function getClearedType() : string
    {
        return Transaction::MODELNAME;
    }

    /**
     * getAmount analog for Assignment model.
     *
     * @return float
     */
    public function getAmount(): float
    {
        $amount = 0;

        if ($this->isPosted()) {
            foreach ($this->ledgers->where("entry_type", Balance::DEBIT) as $ledger) {
                $amount += $ledger->amount / $this->exchangeRate->rate;
            }
        } else {
            foreach ($this->getLineItems() as $lineItem) {
                $amount += $lineItem->amount;
                $amount += $lineItem->amount * $lineItem->vat->rate / 100;
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
    public function addLineItem(LineItem $lineItem) : void
    {
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction(_("add LineItem to"));
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
    public function removeLineItem(LineItem $lineItem) : void
    {
        if (count($lineItem->ledgers) > 0) {
            throw new PostedTransaction(_("remove LineItem from"));
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
        $year = ReportingPeriod::year($this->date);

        $period = ReportingPeriod::where("year",$year)->first();

        if ($period->status == ReportingPeriod::CLOSED) {
            throw new ClosedReportingPeriod($year);
        }

        if ($period->status == ReportingPeriod::ADJUSTING AND $this->transaction_type != Transaction::JN) {
            throw new AdjustingReportingPeriod();
        }

        $this->transaction_no = Transaction::transactionNo(
            $this->transaction_type,
            Carbon::parse($this->date)
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
    public function checkIntegrity(): bool
    {
        // verify transaction ledger hashes
        return $this->ledgers->every(
            function ($ledger, $key) {
                return password_verify($ledger->hashed(), $ledger->hash);
            }
        );
    }
}
