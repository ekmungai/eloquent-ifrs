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

use IFRS\Traits\Recycling;

/**
 * Class Currency
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property string $currency_code
 * @property string $name
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Currency extends Model implements Recyclable
{
    use SoftDeletes;
    use Recycling;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ifrs_currencies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'currency_code',
    ];

    /**
     * Currency Exchange Rates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class);
    }

    /**
     * Currency attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
