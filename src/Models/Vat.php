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
use Illuminate\Support\Facades\DB;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingVatAccount;
use IFRS\Exceptions\InvalidAccountType;
use IFRS\Exceptions\MultipleVatError;

/**
 * Class Vat
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Account $account
 * @property string $code
 * @property string $name
 * @property float $rate
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Vat extends Model implements Segregatable, Recyclable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'rate',
        'valid_from',
        'account_id',
        'valid_to',
        'entity_id',
    ];

    /**
     * Apply multiple VATs to Line Item.
     * 
     * @param array $vatIds
     * @param int $lineItemId
     * @param boolean $compound
     * @return array
     */
    public static function applyMultiple(array $vatIds, int $lineItemId, bool $compound = false) : array
    {
        $vatLineItems = [];
        $lineItem = LineItem::find($lineItemId);
        $zeroRate = Vat::where('rate', 0)->get()->first();
        $chargeAmount = $lineItem->amount * $lineItem->quantity;

        if(count($vatIds) < 2){
            throw new MultipleVatError('There must be at least two Vat Ids');
        }

        if($chargeAmount == 0){
            throw new MultipleVatError('Line Item amount must be non zero');
        }

        if(is_null($zeroRate)){
            throw new MultipleVatError('VAT Line Items require a Zero rated Vat object');
        }

        DB::beginTransaction();

        foreach($vatIds as $vatId){
            $vat = Vat::find($vatId);

            if($vat->rate == 0){
                DB::rollBack();
                throw new MultipleVatError('Zero rated taxes cannot be applied');
            }
            $tax = $chargeAmount * $vat->rate/100;
            $vatLineItems[] = LineItem::create([
                'vat_id' => $zeroRate->id,
                'account_id' => $vat->account_id,
                'narration' => $vat->rate.'% ' .$vat->name. ' Tax on '.$chargeAmount,
                'amount' => $tax
            ]);
            $chargeAmount += $compound ? $tax : 0;
        }

        DB::commit();
        return $vatLineItems;
    }

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false) : string
    {
        $classname = explode('\\', self::class);
        $description = $this->name . ' (' . $this->code . ') at ' . number_format($this->rate, 2) . '%';
        return $type ? array_pop($classname) . ': ' . $description : $description;
    }

    /**
     * Vat attributes.
     *
     * @return object
     */
    public function attributes() : object
    {
        return (object)$this->attributes;
    }

    /**
     * LineItem Vat Account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    /**
     * Vat Validation.
     */
    public function save(array $options = []): bool
    {
        if (!is_null($this->rate)) {
            $this->rate = abs($this->rate);
        }

        if ($this->rate == 0) {
            $this->account_id = null;
        }

        if ($this->rate > 0 && is_null($this->account_id)) {
            throw new MissingVatAccount($this->rate);
        }

        if ($this->rate > 0 && $this->account->account_type != Account::CONTROL) {
            throw new InvalidAccountType('Vat', Account::CONTROL);
        }

        return parent::save();
    }
}
