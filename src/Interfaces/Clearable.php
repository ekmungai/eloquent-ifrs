<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Interfaces;

/**
 *
 * @author emung
 */
interface Clearable
{
    /**
     * Cleared Transaction amount.
     *
     * @return float
     */
    public function getClearedAmountAttribute();

    /**
     * Cleared Object type.
     *
     * @return string
     */
    public function getClearedTypeAttribute();
}
