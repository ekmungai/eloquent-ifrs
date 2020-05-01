<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Interfaces;

/**
 *
 * @author emung
 *
 */
interface Recyclable
{
    /**
     * Model recycling events.
     *
     * @return void
     */
    public static function bootRecycling();

    /**
     * Recycled Model records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function recycled();
}
