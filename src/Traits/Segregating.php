<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Traits;

use Illuminate\Support\Facades\Auth;

use IFRS\Models\Entity;

use IFRS\Scopes\EntityScope;

trait Segregating
{
    /**
     * Register EntityScope for Model.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function bootSegregating()
    {
        static::addGlobalScope(new EntityScope());

        static::creating(
            function ($model) {
                if (Auth::check() && is_null($model->entity_id)) {
                    $model->entity_id = Auth::user()->entity->id;
                }
            }
        );
    }

    /**
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}
