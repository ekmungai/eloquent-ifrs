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

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
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
class ReportingPeriod extends Model implements Segregatable, Recyclable
{
    use Segregating;
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
        'calendar_year',
        'status',
        'entity_id',
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
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        return $type ? array_pop($classname) . ': ' . $this->calendar_year : $this->calendar_year;
    }

    /**
     * Fetch reporting period for the date
     *
     * @param string|Carbon $date
     * @return self
     */
    public static function getPeriod($date = null)
    {
        $year = ReportingPeriod::year($date);
        
        $period = ReportingPeriod::where("calendar_year", $year)->first();
        if (is_null($period)) {
            throw new MissingReportingPeriod(Auth::user()->entity->name, $year);
        }
        return $period;
    }

    /**
     * ReportingPeriod year
     *
     * @param string | Carbon $date
     *
     * @return int
     */
    public static function year($date = null)
    {
        if (is_null(Auth::user()->entity)) {
            return date("Y");
        }

        $year = is_null($date) ? date("Y") : date("Y", strtotime($date));
        $month = is_null($date) ? date("m") : date("m", strtotime($date));

        $year  = intval($month) < Auth::user()->entity->year_start ? intval($year) - 1 : $year;

        return intval($year);
    }

    /**
     * ReportingPeriod start date
     *
     * @return string|Carbon $date
     */
    public static function periodStart($date = null)
    {
        if (is_null(Auth::user()->entity)) {
            return date("Y");
        }

        return Carbon::create(
            ReportingPeriod::year($date),
            Auth::user()->entity->year_start,
            1
        );
    }

    /**
     * ReportingPeriod end date
     *
     * @return string|Carbon
     */
    public static function periodEnd($date = null)
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
