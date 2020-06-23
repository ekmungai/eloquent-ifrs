<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use IFRS\Interfaces\Segragatable;
use IFRS\Interfaces\Recyclable;

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

/**
 * Class Category
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property string $category_type
 * @property string $name
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Category extends Model implements Segragatable, Recyclable
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
        'category_type',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        return $type ? $this->type . ' ' . array_pop($classname) . ': ' . $this->name : $this->name;
    }

    /**
     * Instance Type.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Account::getType($this->category_type);
    }

    /**
     * Category Accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Category attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
