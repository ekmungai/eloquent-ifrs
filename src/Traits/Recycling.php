<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Traits;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Ekmungai\IFRS\Models\RecycledObject;

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
                    $user = Auth::user();
                    RecycledObject::create([
                            'user_id' => $user->id,
                            'entity_id' => $user->entity->id,
                            'recyclable_id' => $model->id,
                            'recyclable_type' => static::class,
                        ]);

                    if ($model->forceDeleting) {
                        $model->destroyed_at = Carbon::now()->toDateTimeString();
                        $model->save();
                        $model->forceDeleting = false;
                    }
                }
            }
        );
        static::restoring(
            function ($model) {
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
        return $this->morphMany('Ekmungai\IFRS\Models\RecycledObject', 'recyclable');
    }
}
