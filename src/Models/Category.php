<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace IFRS\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use IFRS\Interfaces\Segragatable;
use IFRS\Interfaces\Recyclable;

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;

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
