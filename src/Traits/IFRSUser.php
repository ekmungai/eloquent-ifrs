<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Ezeugwu Paschal
 * @copyright Ezeugwu Paschal, 2020, Nigeria
 * @license   MIT
 */
namespace IFRS\Traits;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segragating;
use IFRS\Models\Entity;

trait IFRSUser
{
    use Recycling;
    use Segragating;

    /**
     * User's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * User attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
