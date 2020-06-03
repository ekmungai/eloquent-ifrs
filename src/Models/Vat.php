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

use IFRS\Interfaces\Segragatable;
use IFRS\Interfaces\Recyclable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segragating;
use IFRS\Traits\ModelTablePrefix;
// use IFRS\Exceptions\VatPeriodOverlap;

/**
 * Class Vat
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property string $code
 * @property string $name
 * @property float $rate
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Vat extends Model implements Segragatable, Recyclable
{
    use Segragating;
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
        'valid_to',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $description = $this->name.' ('.$this->code.') at '.number_format($this->rate, 2).'%';
        return $type? 'VAT: '.$description : $description;
    }

    /**
     * Vat attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Vat Validation.
     */
    public function save(array $options = []) : bool
    {
//         $open = Vat::where('valid_from','<=', $this->valid_from)->whereNull('valid_to');

//         $closed = Vat::where('valid_from','<=', $this->valid_from)->where('valid_to','>', $this->valid_from);

//         if (count($open->get()) || count($closed->get())) {
//             throw new VatPeriodOverlap();
//         }
        return parent::save();
    }
}
