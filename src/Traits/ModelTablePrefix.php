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

        $table = parent::getTable();
        $prefix = (string) config('ifrs.table_prefix');

        return strpos($table, $prefix) !== false? $table: $prefix.$table;
    }
}
