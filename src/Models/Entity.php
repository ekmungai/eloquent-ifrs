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

use Ekmungai\IFRS\Interfaces\Recyclable;

use Ekmungai\IFRS\Traits\Recycling;

/**
 * Class Entity
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Currency $currency
 * @property string $name
 * @property bool $multi_currency
 * @property integer $year_start
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Entity extends Model implements Recyclable
{
    use SoftDeletes;
    use Recycling;

    /**
     * Construct new Entity.
     *
     * @param string $name
     * @param Currency $currency
     *
     * @return Entity
     */
    public static function new(string $name, Currency $currency, int $yearStart = 1, bool $multiCurrency = false) : Entity
    {
        $entity = new Entity();

        $entity->name = $name;
        $entity->currency_id = $currency->id;
        $entity->year_start = $yearStart;
        $entity->multi_currency = $multiCurrency;

        return $entity;
    }

    /**
     * Users associated with the reporting Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Entity's Reporting Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Reporting Currency Default Rate.
     *
     * @return ExchangeRate
     */
    public function defaultRate() : ExchangeRate
    {
        $existing = ExchangeRate::find([
            "entity_id" => $this->id,
            "currency_id" => $this->currency_id,
            "valid_from" => Carbon::now(),
        ])->first();

        $new = ExchangeRate::new(Carbon::now(), null, $this->currency);
        $new->save();

        return !is_null($existing)? $existing : $new;
    }

    /**
     * Entity's Reporting Periods.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reportingPeriods()
    {
        return $this->hasMany(ReportingPeriod::class);
    }

    /**
     * Entity attributes
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
