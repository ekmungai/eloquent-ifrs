<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Segragatable;
use Ekmungai\IFRS\Interfaces\Recyclable;

use Ekmungai\IFRS\Traits\Segragating;
use Ekmungai\IFRS\Traits\Recycling;

/**
 * Class ExchangeRate
 *
 * @package Ekmungai\Laravel-IFRS
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

    /**
     * Construct new Exchange Rate.
     *
     * @param Carbon $validFrom
     * @param Carbon $validto
     * @param Currency $currency
     * @param float $rate
     *
     * @return ExchangeRate
     */
    public static function new(
        Carbon $validFrom,
        Carbon $validto = null,
        Currency $currency,
        float $rate = 1.0
    ) : ExchangeRate {
        $exchangeRate = new ExchangeRate();

        $exchangeRate->valid_from = $validFrom;
        $exchangeRate->valid_to = $validto;
        ;
        $exchangeRate->currency_id = $currency->id;
        $exchangeRate->rate = $rate;

        return $exchangeRate;
    }

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
