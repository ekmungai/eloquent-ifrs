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
interface Clearable
{
    /**
     * Cleared Transaction amount.
     *
     * @return float
     */
    public function clearedAmount();

    /**
     * Cleared Transaction number.
     *
     * @return string
     */
    public function getTransactionNo();

    /**
     * Get Transaction Id.
     *
     * @return int
     */
    public function getId();

    /**
     * Cleared Object type.
     *
     * @return string
     */
    public function getClearedType();
}
