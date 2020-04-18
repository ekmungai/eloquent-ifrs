<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace Ekmungai\IFRS\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use Ekmungai\IFRS\Interfaces\Segragatable;
use Ekmungai\IFRS\Interfaces\Recyclable;

use Ekmungai\IFRS\Traits\Segragating;
use Ekmungai\IFRS\Traits\Recycling;

/**
 * Class Category
 *
 * @package Ekmungai\Laravel-IFRS
 *
 * @property Entity $entity
 * @property string $category_type
 * @property string $name
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class Category extends Model implements Segragatable, Recyclable
{
    use Segragating;
    use SoftDeletes;
    use Recycling;

    /**
     * Construct new Category.
     *
     * @param string $name
     * @param string $categoryType
     *
     * @return Category
     */
    public static function new(string $name, string $categoryType) : Category
    {
        $category = new Category();

        $category->name = $name;
        $category->category_type = $categoryType;

        return $category;
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
}
