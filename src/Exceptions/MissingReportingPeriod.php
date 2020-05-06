<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Exceptions;

class MissingReportingPeriod extends IFRSException
{
    /**
     * Missing Reporting Period Exception
     *
     * @param string $entity
     * @param int    $year
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $entity, int $year, string $message = null, int $code = null)
    {
        $error = _("Entity '". $entity."' has no reporting period defined for the year ").$year." ";

        parent::__construct($error.$message, $code);
    }
}
