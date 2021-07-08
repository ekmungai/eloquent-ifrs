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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use IFRS\Exceptions\MissingReportingCurrency;
use IFRS\Exceptions\UnconfiguredLocale;

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
        'parent_id',
        'year_start',
        'multi_currency',
        'locale',
    ];

    /**
     * Entity's Reporting Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

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
     * Model's Parent Entity (if exists).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Model's Daughter Entities (if any).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function daughters()
    {
        return $this->hasMany(Entity::class, 'parent_id', 'id');
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
     * Entity's Registered Currencies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function currencies()
    {
        return $this->hasMany(Currency::class);
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
            'valid_from' => $now,
            'currency_id' => $this->reportingCurrency->id,
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
     * Reporting Currency for the Entity.
     *
     * @return Currency
     */
    public function getReportingCurrencyAttribute(): Currency
    {
        if (is_null($this->currency) && is_null($this->parent)) {
            throw new MissingReportingCurrency($this->name);
        }

        return is_null($this->parent) ? $this->currency : $this->parent->currency;
    }

    /**
     * Format the given amount and currency according to the given locale.
     *
     * @param float $amount
     * @param string $currencyCode
     * @param string $locale
     * @return string
     */
    public function localizeAmount(float $amount, string $currencyCode = null, $locale = null){
        if(is_null($locale)){
            $locale = $this->locale;
        }
        if(is_null($currencyCode)){
            $currencyCode = $this->reportingCurrency->currency_code;
        }

        $format = \NumberFormatter::create($locale, \NumberFormatter::CURRENCY );
        return $format->formatCurrency($amount, $currencyCode);
    }

    /**
     * Validate Entity.
     */
    public function save(array $options = []): bool
    {
        if(is_null($this->locale)){
            $this->locale = config('ifrs.locales')[0];
        }else{
            if(!in_array($this->locale, config('ifrs.locales'))){
                throw new UnconfiguredLocale($this->locale);
            }
        }

        return parent::save();
    }
}
