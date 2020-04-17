<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Interfaces;

/**
 *
 * @author emung
 *
 */
interface Sells
{
    /**
     * Validate Selling Transaction Main Account.
     */
    public function save() : void;

    /**
     * Validate Selling Transaction LineItems.
     */
    public function post() : void;
}
