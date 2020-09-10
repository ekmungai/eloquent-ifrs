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

    /**
     * Test Category Accounts balances
     *
     * @return void
     */
    // public function testCategoryAccountsBalances()
    // {
    //     $category1 = factory(Category::class)->create([
    //         "name" => "Category One",
    //         'category_type' => Account::RECEIVABLE
    //     ]);

    //     $category2 = factory(Category::class)->create([
    //         "name" => "Category Two",
    //         'category_type' => Account::OPERATING_REVENUE
    //     ]);

    //     $account1 = new Account([
    //         'name' => $this->faker->name,
    //         'account_type' => Account::RECEIVABLE,
    //         'category_id' => $category1->id
    //     ]);
    //     $account1->save();

    //     $account2 = new Account([
    //         'name' => $this->faker->name,
    //         'account_type' => Account::OPERATING_REVENUE,
    //         'category_id' => $category2
    //     ]);
    //     $account2->save();

    //     $account3 = new Account([
    //         'name' => $this->faker->name,
    //         'account_type' => Account::RECEIVABLE,
    //         'category_id' => $category2->id
    //     ]);
    //     $account3->save();

    //     $account4 = new Account([
    //         'name' => $this->faker->name,
    //         'account_type' => Account::RECEIVABLE,
    //         'category_id' => $category2->id
    //     ]);
    //     $account4->save();

    //     factory(Balance::class, 3)->create([
    //         "account_id" => $account1->id,
    //         "balance_type" => Balance::DEBIT,
    //         "exchange_rate_id" => factory(ExchangeRate::class)->create([
    //             "rate" => 1
    //         ])->id,
    //         'reporting_period_id' => $this->period->id,
    //         "amount" => 50
    //     ]);

    //     factory(Balance::class, 2)->create([
    //         "account_id" => $account1->id,
    //         "balance_type" => Balance::CREDIT,
    //         "exchange_rate_id" => factory(ExchangeRate::class)->create([
    //             "rate" => 1
    //         ])->id,
    //         'reporting_period_id' => $this->period->id,
    //         "amount" => 40
    //     ]);

    //     //Client Invoice Transaction
    //     $clientInvoice = new ClientInvoice([
    //         "account_id" => $account2->id,
    //         "date" => Carbon::now(),
    //         "narration" => $this->faker->word,
    //     ]);

    //     $line = new LineItem([
    //         'vat_id' => factory(Vat::class)->create([
    //             "rate" => 16,
    //             "account_id" => $account4->id
    //         ])->id,
    //         'account_id' => $account3->id,
    //         'narration' => $this->faker->sentence,
    //         'quantity' => $this->faker->randomNumber(),
    //         'amount' => 100,
    //         'quantity' => 1,
    //     ]);

    //     $clientInvoice->addLineItem($line);

    //     $clientInvoice->post();

    //     $clients = Account::sectionBalances([Account::RECEIVABLE]);

    //     $incomes = Account::sectionBalances([Account::OPERATING_REVENUE]);

    //     $control = Account::sectionBalances([Account::CONTROL]);

    //     $this->assertTrue(in_array($category1, array_keys($clients["sectionCategories"])));
    //     $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->id, $account1->id);
    //     $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->openingBalance, 70);
    //     $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->currentBalance, 0);
    //     $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->closingBalance, 70);
    //     $this->assertEquals($clients["sectionCategories"][$category1]["total"], 70);

    //     $this->assertTrue(in_array($category2, array_keys($clients["sectionCategories"])));
    //     $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->id, $account2->id);
    //     $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->openingBalance, 0);
    //     $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->currentBalance, 116);
    //     $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->closingBalance, 116);
    //     $this->assertEquals($clients["sectionCategories"][$category2]["total"], 116);

    //     $this->assertEquals($clients["sectionTotal"], 186);

    //     $this->assertTrue(in_array($category3, array_keys($incomes["sectionCategories"])));
    //     $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->id, $account3->id);
    //     $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->openingBalance, 0);
    //     $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->currentBalance, -100);
    //     $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->closingBalance, -100);
    //     $this->assertEquals($incomes["sectionCategories"][$category3]["total"], -100);

    //     $this->assertEquals($incomes["sectionTotal"], -100);

    //     $this->assertTrue(in_array($category4, array_keys($control["sectionCategories"])));
    //     $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->id, $account4->id);
    //     $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->openingBalance, 0);
    //     $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->currentBalance, -16);
    //     $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->closingBalance, -16);
    //     $this->assertEquals($control["sectionCategories"][$category4]["total"], -16);

    //     $this->assertEquals($control["sectionTotal"], -16);
    // }
}
