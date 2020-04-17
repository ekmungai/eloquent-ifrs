<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Traits;

use Illuminate\Support\Facades\Auth;

use App\Models\Entity;

use App\Scopes\EntityScope;

use App\Exceptions\UnauthorizedUser;

trait Segragating
{

    /**
     * Register EntityScope for Model.
     *
     * @return null
     */
    public static function bootSegragating()
    {
        static::addGlobalScope(new EntityScope);

        static::creating(
            function ($model) {

                // only users can be created without requiring to be logged on
                if (!Auth::check() && !is_a($model, "App\Models\User")) {
                    throw new UnauthorizedUser();
                }

                if (Auth::check()) {
                    $model->entity_id = Auth::user()->entity->id;
                }
            }
        );
        return null;
    }

    /**
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity()
    {
        return $this->hasOne(Entity::class);
    }
}
