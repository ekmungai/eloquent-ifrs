<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

/**
 * Class RecycledObject
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property Recyclable $recyclable
 * @property User $user
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Segragatable;

use Ekmungai\IFRS\Traits\Segragating;

class RecycledObject extends Model implements Segragatable
{
    use Segragating;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'entity_id', 'recyclable_id', 'recyclable_type',
    ];

    /**
     * Recycled object.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recyclable()
    {
        return $this->morphTo();
    }

    /**
     * User responsible for the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RecycledObject attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
