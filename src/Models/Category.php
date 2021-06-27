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

use IFRS\Interfaces\Recyclable;
use IFRS\Interfaces\Segregatable;

use IFRS\Traits\Recycling;
use IFRS\Traits\Segregating;
use IFRS\Traits\ModelTablePrefix;
use Illuminate\Support\Facades\Auth;

/**
 * Class Category
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property string $category_type
 * @property string $name
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Category extends Model implements Segregatable, Recyclable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category_type',
        'entity_id',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        return $type ? $this->type . ' ' . array_pop($classname) . ': ' . $this->name : $this->name;
    }

    /**
     * Instance Type.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return Account::getType($this->category_type);
    }

    /**
     * Category Accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Category attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }

    /**
     * Category Accounts Balances for the given period.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     *  
     * @return array
     */
    public function getAccountBalances(Carbon $startDate = null, Carbon $endDate = null)
    {
        $balances = ["total" => 0, "accounts" => []];

        $entity = Entity::where('id','=',$this->entity_id)->first();

        $reportingCurrency = $entity->currency_id;

        $periodStart = ReportingPeriod::periodStart($endDate,$entity);
        $year = ReportingPeriod::year($endDate,$entity);

        foreach ($this->accounts as $account) {

            $closingBalance = $account->currentBalance($startDate, $endDate,null,$entity->id)[$reportingCurrency];
            if ($startDate == $periodStart) {
                $closingBalance += $account->openingBalance($year,null,$entity->id)[$reportingCurrency];
            }

            if ($closingBalance != 0) {
                $account->closingBalance = $closingBalance;

                $balances["accounts"][] = $account;
                $balances["total"] += $closingBalance;
            }
        }

        return $balances;
    }
}
