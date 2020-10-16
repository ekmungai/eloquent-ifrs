<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use Carbon\Carbon;
use IFRS\Exceptions\DuplicateAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Interfaces\Recyclable;

use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

/**
 * Class Entity
 *
 * @package Ekmungai\Eloquent-IFRS
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
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'currency_id',
        'year_start',
        'multi_currency',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        return $type ? array_pop($classname) . ': ' . $this->name : $this->name;
    }

    /**
     * Users associated with the reporting Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(config('ifrs.user_model'));
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

    /**
     * Reporting Currency Default Rate.
     *
     * @return ExchangeRate
     */
    public function getDefaultRateAttribute(): ExchangeRate
    {
        $now = Carbon::now();
        $existing = ExchangeRate::where([
            "entity_id" => $this->id,
            "currency_id" => $this->currency_id,
        ])->where("valid_from", "<=", $now)
            ->first();

        if (!is_null($existing)) {
            return $existing;
        }

        $new = new ExchangeRate([
            'valid_from' => Carbon::now(),
            'currency_id' => $this->currency->id,
            "rate" => 1
        ]);

        $new->save();

        return $new;
    }


    /**
     * Current Reporting Period for the Entity.
     *
     * @return ReportingPeriod
     */
    public function getCurrentReportingPeriodAttribute(): ReportingPeriod
    {
        $existing = $this->reportingPeriods->where('calendar_year', date("Y"))->first();

        if (!is_null($existing)) {
            return $existing;
        }

        $new = new ReportingPeriod([
            'calendar_year' => date('Y'),
            'period_count' => count(ReportingPeriod::withTrashed()->get()) + 1,
        ]);

        $new->save();

        return $new;
    }

    /**
     * Associate Currency.
     */
    public function save(array $options = []): bool
    {
        $currency = Currency::find($this->currency_id);

        if (!is_null($currency->entity_id)) {
            throw new DuplicateAssignment();
        }
        parent::save($options);

        $currency->entity_id = $this->id;

        return $currency->save();
    }
}
