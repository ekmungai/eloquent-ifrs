<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Traits;

use Carbon\Carbon;
use IFRS\Models\RecycledObject;
use Illuminate\Support\Facades\Auth;

trait Recycling
{
    /**
     * Model recycling events.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function bootRecycling()
    {
        // recycling only functions when a user is logged in
        static::deleting(
            function ($model) {
                if (Auth::check()) {
                    if ($model->forceDeleting) {
                        $model->destroyed_at = Carbon::now()->toDateTimeString();
                        $model->save();
                        $model->forceDeleting = false;
                    } else {
                        $user = Auth::user();
                        if ($user->entity) {
                            RecycledObject::create(
                                [
                                    'user_id' => $user->id,
                                    'entity_id' => $user->entity->id,
                                    'recyclable_id' => $model->id,
                                    'recyclable_type' => static::class,
                                ]
                            );
                        }
                    }
                }
            }
        );
        static::restoring(
            function ($model) {
                if (!is_null($model->destroyed_at)) {
                    return false;
                }

                if (Auth::check()) {
                    $recycled = $model->recycled->last();
                    $recycled->delete();
                }
            }
        );
    }

    /**
     * Recycled Model records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function recycled()
    {
        return $this->morphMany(RecycledObject::class, 'recyclable');
    }
}
