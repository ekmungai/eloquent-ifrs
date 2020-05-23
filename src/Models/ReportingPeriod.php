<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use IFRS\Interfaces\Segragatable;
use IFRS\Interfaces\Recyclable;

use IFRS\Traits\Segragating;
use IFRS\Traits\Recycling;
use IFRS\Traits\ModelTablePrefix;

use IFRS\Exceptions\MissingReportingPeriod;

/**
 * Class ReportingPeriod
 *
 * @package Ekmungai\Eloquent-IFRS
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
    use ModelTablePrefix;

    /**
     * Reporting Period Status
     *
     * @var string
     */

    const OPEN = "OPEN";
    const CLOSED = "CLOSED";
    const ADJUSTING = "ADJUSTING";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'period_count',
        'year',
        'status',
    ];

    /**
     * Construct new Account.
     */
    public function __construct($attributes = [])
    {
        if (!isset($attributes['status'])) {
            $attributes['status'] = ReportingPeriod::OPEN;
        }
        return parent::__construct($attributes);
    }

    /**
     * Fetch reporting period for the date
     *
     * @return int
     */
    public static function getPeriod(string $date = null)
    {
        $year = ReportingPeriod::year($date);

        $period = ReportingPeriod::where("year", $year)->first();
        if (is_null($period)) {
            throw new MissingReportingPeriod(Auth::user()->entity->name, $year);
        }
        return $period;
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
