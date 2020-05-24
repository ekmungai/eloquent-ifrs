<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Ezeugwu Paschal
 * @copyright Ezeugwu Paschal, 2020, Nigeria
 * @license   MIT
 */
namespace IFRS\Traits;

/**
 *
 * @author @paschaldev
 */
trait ModelTablePrefix
{
    /**
     * Determine the model table name
     */
    public function getTable() {
        return config('ifrs.table_prefix').parent::getTable();
    }
}
