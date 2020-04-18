<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Recyclable;
use Ekmungai\IFRS\Interfaces\Segragatable;

use Ekmungai\IFRS\Traits\Segragating;
use Ekmungai\IFRS\Traits\Recycling;

use Ekmungai\IFRS\Exceptions\MissingVatAccount;
use Ekmungai\IFRS\Exceptions\NegativeAmount;

/**
 * Class LineItem
 *
 * @package Ekmungai\Laravel-IFRS
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

    /**
     * Construct new LineItem.
     *
     * @param Account $account
     * @param Vat $vat
     * @param float $amount
     * @param int $quantity
     * @param string $descripion
     * @param Account $vatAccount
     *
     * @return LineItem
     */
    public static function new(
        Account $account,
        Vat $vat,
        float $amount,
        int $quantity = 1,
        string $descripion = null,
        Account $vatAccount = null
    ) : LineItem {
        $lineItem = new LineItem();

        $lineItem->account_id = $account->id;
        $lineItem->vat_id  = $vat->id;
        $lineItem->amount = $amount;
        $lineItem->quantity = $quantity;
        $lineItem->description = $descripion;
        $lineItem->vat_account_id = !is_null($vatAccount)? $vatAccount->id : $vatAccount;

        return $lineItem;
    }

    /**
     * LineItem Ledgers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers()
    {
        return $this->HasMany('Ekmungai\IFRS\Models\Ledger', 'line_item_id', 'id');
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
        return $this->HasOne('Ekmungai\IFRS\Models\Account', 'id', 'vat_account_id');
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

        return parent::save();
    }
}
