<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\Category;
use IFRS\Models\RecycledObject;
use IFRS\Models\User;

class CategoryTest extends TestCase
{
    /**
     * Test Category model Entity Scope.
     *
     * @return void
     */
    public function testCategoryEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $category = new Category([
            'name' => $this->faker->word,
            'category_type' => $this->faker->randomElement(
                array_keys(config('ifrs')['accounts'])
                ),
        ]);
        $category->save();

        $category->attributes();

        $this->assertEquals(count(Category::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Category::all()), 0);
    }

    /**
     * Test Category Model recylcling
     *
     * @return void
     */
    public function testCategoryRecycling()
    {
        $category = new Category([
            'name' => $this->faker->word,
            'category_type' => $this->faker->randomElement(
                array_keys(config('ifrs')['accounts'])
                ),
        ]);
        $category->save();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($category->recycled->first(), $recycled);
    }
}
