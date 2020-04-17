<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use App\Interfaces\Segragatable;
use App\Interfaces\Recyclable;

use App\Traits\Segragating;
use App\Traits\Recycling;

use App\Exceptions\MissingReportingPeriod;

/**
 * Class ReportingPeriod
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Entity $entity
 * @property integer $year
 * @property integer $period_count
 * @property string $status
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class ReportingPeriod extends Model implements Segragatable, Recyclable
{
    use Segragating;
    use SoftDeletes;
    use Recycling;

    /**
     * Construct new ReportingPeriod
     *
     * @param int $periodCount
     * @param int $year
     *
     * @return ReportingPeriod
     */
    public static function new(int $periodCount, int $year) : ReportingPeriod
    {
        $reportingPeriod = new ReportingPeriod();

        $reportingPeriod->period_count = $periodCount;
        $reportingPeriod->year = $year;

        return $reportingPeriod;
    }

    /**
     * ReportingPeriod Period Count
     *
     * @return int
     */
    public static function periodCount(string $date = null)
    {
        $year = ReportingPeriod::year($date);

        $period = ReportingPeriod::where("year", $year)->first();
        if (is_null($period)) {
            throw new MissingReportingPeriod(Auth::user()->entity->name, $year);
        }
        return $period->period_count;
    }

    /**
     * ReportingPeriod year
     *
     * @param string $date
     *
     * @return int
     */
    public static function year(string $date = null)
    {
        $year = is_null($date) ? date("Y") : date("Y", strtotime($date));
        $month = is_null($date) ? date("m") : date("m", strtotime($date));

        $year  = intval($month) < Auth::user()->entity->year_start ? intval($year)-1 : $year;

        return intval($year);
    }

    /**
     * ReportingPeriod start string
     *
     * @return Carbon
     */
    public static function periodStart(string $date = null)
    {
        return Carbon::create(
            ReportingPeriod::year($date),
            Auth::user()->entity->year_start,
            1
        );
    }

    /**
     * ReportingPeriod end string
     *
     * @return string
     */
    public static function periodEnd(string $date = null)
    {
        return ReportingPeriod::periodStart($date)
        ->addYear()
        ->subDay();
    }

    /**
     * ReportingPeriod attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
