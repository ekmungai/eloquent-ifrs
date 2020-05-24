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

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

/**
 * Class ExchangeRate
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Currency $currency
 * @property Carbon $valid_from
 * @property Carbon $valid_to
 * @property float $rate
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class ExchangeRate extends Model implements Segragatable, Recyclable
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
    protected $table = 'ifrs_exchange_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'valid_from',
        'valid_to',
        'currency_id',
        'rate',
    ];

    /**
     * Exchange Rate Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->BelongsTo(Currency::class);
    }

    /**
     * ExchangeRate attributes
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
