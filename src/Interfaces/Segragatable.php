<?php
/**
 * Laravel IFRS Accounting
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
interface Segragatable
{
    /**
     * Register EntityScope for Model.
     *
     * @return null
     */
    public static function bootSegragating();

    /**
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity();
}
