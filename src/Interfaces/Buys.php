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
interface Buys
{
    /**
     * Validate Buying Transaction Main Account.
     *
     * @return void
     */
    public function save() : void;

    /**
     * Validate Buying Transaction LineItems.
     *
     * @return void
     */
    public function post() : void;
}
