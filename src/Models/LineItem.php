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

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segragatable;

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingVatAccount;
use IFRS\Exceptions\NegativeAmount;
use IFRS\Exceptions\PostedTransaction;

/**
 * Class LineItem
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Transaction $transaction
 * @property Vat $vat
 * @property Account $account
 * @property Account $vatAccount
 * @property Carbon $date
 * @property int $quantity
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class LineItem extends Model implements Recyclable, Segragatable
{
    use Segragating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ifrs_line_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'vat_id',
        'amount',
        'quantity',
        'description',
        'vat_account_id',
    ];

    /**
     * LineItem Ledgers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers()
    {
        return $this->HasMany('IFRS\Models\Ledger', 'line_item_id', 'id');
    }

    /**
     * LineItem Transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * LineItem Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * LineItem VAT.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

    /**
     * LineItem Vat Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vatAccount()
    {
        return $this->HasOne('IFRS\Models\Account', 'id', 'vat_account_id');
    }

    /**
     * LineItem attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Check LineItem Vat.
     */
    public function save(array $options = []): bool
    {
        if ($this->vat->rate > 0 and is_null($this->vat_account_id)) {
            throw new MissingVatAccount($this->vat->name);
        }

        if ($this->amount < 0) {
            throw new NegativeAmount("LineItem");
        }

        if (!is_null($this->transaction) and count($this->transaction->ledgers) > 0 and $this->isDirty()) {
            throw new PostedTransaction(_("change a LineItem of"));
        }

        return parent::save();
    }
}
