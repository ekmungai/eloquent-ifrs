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
use Illuminate\Support\Facades\Auth;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;
use IFRS\Traits\Segregating;

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
class Currency extends Model implements Recyclable, Segregatable
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
        'currency_code',
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
        return $type ? array_pop($classname) . ': ' . $this->name . ' (' . $this->currency_code . ')' : $this->name . ' (' . $this->currency_code . ')';
    }

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
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Currency attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object)$this->attributes;
    }

    /**
     * Associate Entity.
     */
    public function save(array $options = []): bool
    {
        if (!isset($this->entity_id)) {
            $this->entity_id = Auth::user()->entity->id;
        }

        return parent::save($options);
    }
}
