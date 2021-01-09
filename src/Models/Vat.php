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
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingVatAccount;
use IFRS\Exceptions\InvalidAccountType;

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
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
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
    public function attributes()
    {
        return (object) $this->attributes;
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

        if ($this->rate > 0 && is_null($this->account_id)) {
            throw new MissingVatAccount($this->rate);
        }

        if ($this->rate > 0 && $this->account->account_type != Account::CONTROL) {
            throw new InvalidAccountType(Account::CONTROL);
        }

        return parent::save();
    }
}
