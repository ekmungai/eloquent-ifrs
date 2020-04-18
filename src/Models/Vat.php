<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Ekmungai\IFRS\Interfaces\Segragatable;
use Ekmungai\IFRS\Interfaces\Recyclable;

use Ekmungai\IFRS\Traits\Recycling;
use Ekmungai\IFRS\Traits\Segragating;

/**
 * Class Vat
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Entity $entity
 * @property string $code
 * @property string $name
 * @property float $rate
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Vat extends Model implements Segragatable, Recyclable
{
    use Segragating;
    use SoftDeletes;
    use Recycling;

    /**
     * Construct new Vat
     *
     * @param string $name
     * @param string $code
     * @param float $rate
     *
     * @return Vat
     */
    public static function new(string $name, string $code, float $rate) : Vat
    {
        $vat = new Vat();

        $vat->name = $name;
        $vat->code = $code;
        $vat->rate = $rate;

        return $vat;
    }

    /**
     * Vat attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) $this->attributes;
    }
}
