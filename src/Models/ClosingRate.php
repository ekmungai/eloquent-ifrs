<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use IFRS\Exceptions\DuplicateClosingRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

/**
 * Class ClosingRate
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property ExchangeRate $exchangeRate
 * @property ReportingPeriod $reportingPeriod
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class ClosingRate extends Model implements Segregatable, Recyclable
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
        'reporting_period_id',
        'exchange_rate_id',
        'entity_id',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        $exchangeRate = $this->exchangeRate;
        $instanceName = $this->reportingPeriod->calendar_year . ' ' . $exchangeRate->currency->currency_code . ' at ' . $exchangeRate->rate;
        return $type ? array_pop($classname) . ': ' . $instanceName : $instanceName;
    }

    /**
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Closing Rates's Exchange Rate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function exchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    /**
     * Closing Rates's Reporting Period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function reportingPeriod()
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    /**
     * ClosingRate attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object)$this->attributes;
    }

    /**
     * Validate Closing Rate.
     */
    public function save(array $options = []): bool
    {
        $rate = ExchangeRate::find($this->exchange_rate_id);
        $period = ReportingPeriod::find($this->reporting_period_id);

        if (ClosingRate::where('reporting_period_id', $period->id)
                ->whereHas('ExchangeRate', function ($q) use ($rate) {
                    $q->where('currency_id', $rate->currency_id);
                })->count() > 0) {
            throw new DuplicateClosingRate($rate->currency->currency_code, $period->calendar_year);
        }

        return parent::save($options);
    }
}
