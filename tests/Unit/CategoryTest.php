<?php

namespace Tests\Unit;

use Carbon\Carbon;
use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Category;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;
use IFRS\Transactions\ClientInvoice;
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
     * Test Category model categ$category Scope.
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
        $category = Category::create([
            'name' => $this->faker->word,
            'category_type' => $this->faker->randomElement(
                array_keys(config('ifrs')['accounts'])
            ),
        ]);
        $category->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($category->recycled->first(), $recycled);
        $this->assertEquals($recycled->recyclable->id, $category->id);
        
        $category->restore();

        $this->assertEquals(count($category->recycled()->get()), 0);
        $this->assertEquals($category->deleted_at, null);

        //'hard' delete
        $category->forceDelete();

        $this->assertEquals(count(Category::all()), 0);
        $this->assertEquals(count(Category::withoutGlobalScopes()->get()), 1);
        $this->assertNotEquals($category->deleted_at, null);
        $this->assertNotEquals($category->destroyed_at, null);

        //destroyed objects cannot be restored
        $category->restore();

        $this->assertNotEquals($category->deleted_at, null);
        $this->assertNotEquals($category->destroyed_at, null);
    }

    /**
     * Test Category Accounts balances
     *
     * @return void
     */
    public function testCategoryAccountsBalances()
    {
        $clientCategory = factory(Category::class)->create([
            "name" => "Category One",
            'category_type' => Account::RECEIVABLE
        ]);

        $revenueCategory = factory(Category::class)->create([
            "name" => "Category Two",
            'category_type' => Account::OPERATING_REVENUE
        ]);

        $account1 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => $clientCategory->id
        ]);
        $account1->save();

        $account2 = new Account([
            'name' => 'test revenue account',
            'account_type' => Account::OPERATING_REVENUE,
            'category_id' => $revenueCategory->id
        ]);
        $account2->save();

        $account3 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => $clientCategory->id
        ]);
        $account3->save();

        $account4 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::OPERATING_REVENUE,
            'category_id' => $revenueCategory->id
        ]);
        $account4->save();

        factory(Balance::class, 3)->create([
            "account_id" => $account1->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account1->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 40
        ]);

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $account3->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create([
                "rate" => 0,
            ])->id,
            'account_id' => $account2->id,
            'narration' => $this->faker->sentence,
            'quantity' => $this->faker->randomNumber(),
            'amount' => 100,
            'quantity' => 1,
        ]);

        $clientInvoice->addLineItem($line);

        $clientInvoice->post();


        $periodStart = ReportingPeriod::periodStart();

        $clientCategoryBalances = $clientCategory->getAccountBalances($periodStart);
        $revenueCategoryBalances = $revenueCategory->getAccountBalances($periodStart);

        $this->assertEquals($clientCategoryBalances["accounts"][0]->id, $account1->id);
        $this->assertEquals($clientCategoryBalances["accounts"][1]->id, $account3->id);

        $this->assertEquals($clientCategoryBalances["total"], 170);

        $this->assertEquals($revenueCategoryBalances["accounts"][0]->id, $account2->id);

        $this->assertEquals($revenueCategoryBalances["total"], -100);

        $clientCategoryBalances = $clientCategory->getAccountBalances(Carbon::now()->subWeeks(2));
        $this->assertEquals($revenueCategoryBalances["total"], -100);
    }
}
