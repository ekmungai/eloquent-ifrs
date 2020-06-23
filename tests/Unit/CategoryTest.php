<?php

namespace Tests\Unit;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Category;
use IFRS\Models\RecycledObject;
use IFRS\User;

class CategoryTest extends TestCase
{
    /**
     * Category Model relationships test.
     *
     * @return void
     */
    public function testCategoryRelationships()
    {
        $type = $this->faker->randomElement(
            array_keys(config('ifrs')['accounts'])
        );
        $category = new Category([
            'name' => $this->faker->word,
            'category_type' => $type,
        ]);
        $category->save();

        $account = factory(Account::class)->create([
            "account_type" => $type,
            "category_id" => $category->id,
        ]);

        $this->assertEquals($category->accounts->first()->name, $account->name);
        $this->assertEquals(
            $category->toString(true),
            Account::getType($category->category_type) . ' Category: ' . $category->name
        );
        $this->assertEquals(
            $category->toString(),
            $category->name
        );
        $this->assertEquals($category->type, Account::getType($type));
    }

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
