<?php

namespace Tests\Unit;

use Carbon\Carbon;

use Ekmungai\IFRS\Tests\TestCase;

use Ekmungai\IFRS\Models\Account;
use Ekmungai\IFRS\Models\Category;
use Ekmungai\IFRS\Models\Currency;
use Ekmungai\IFRS\Models\RecycledObject;
use Ekmungai\IFRS\Models\User;
use Ekmungai\IFRS\Models\Balance;
use Ekmungai\IFRS\Models\ExchangeRate;
use Ekmungai\IFRS\Models\Ledger;
use Ekmungai\IFRS\Models\Vat;
use Ekmungai\IFRS\Models\LineItem;

use Ekmungai\IFRS\Transactions\ClientInvoice;

use Ekmungai\IFRS\Exceptions\HangingTransactions;
use Ekmungai\IFRS\Exceptions\MissingAccountType;

class AccountTest extends TestCase
{
    /**
     * Account Model relationships test.
     *
     * @return void
     */
    public function testAccountRelationships()
    {
        $this->be(factory(User::class)->create());

        $currency = factory(Currency::class)->create();

        $category = factory(Category::class)->create();

        $account = Account::new(
            $this->faker->name,
            $this->faker->randomElement(array_keys(config('ifrs')['accounts'])),
            $this->faker->sentence,
            $category,
            $currency
        );
        $account->save();

        $this->assertEquals($account->currency->name, $currency->name);
        $this->assertEquals($account->category->name, $category->name);
    }

    /**
     * Test Account model Entity Scope.
     *
     * @return void
     */
    public function testAccountEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $account = Account::new(
            $this->faker->name,
            $this->faker->randomElement(array_keys(config('ifrs')['accounts'])),
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        $this->assertEquals(count(Account::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Account::all()), 0);
    }

    /**
     * Test Account Model recylcling
     *
     * @return void
     */
    public function testAccountRecycling()
    {
        $account = factory(Account::class)->create();
        $account->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($account->recycled->first(), $recycled);
    }

    /**
     * Test Account Codes
     *
     * @return void
     */
    public function testAccountCodes()
    {
        // Manual code
        $account = Account::new(
            $this->faker->name,
            $this->faker->randomElement(array_keys(config('ifrs')['accounts'])),
            $this->faker->sentence,
            factory(Category::class)->create(),
            null,
            6000
        );
        $account->save();

        $this->assertEquals(6000, $account->code);

        // Auto generated code
        $account = Account::new(
            $this->faker->name,
            Account::NON_CURRENT_ASSET,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        $this->assertEquals(1, $account->code);

        factory(Account::class, 3)->create([
            "account_type" => Account::OPERATING_REVENUE,
            "code" => null
        ]);

        $account = Account::new(
            $this->faker->name,
            Account::OPERATING_REVENUE,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        $this->assertEquals(4004, $account->code);

        factory(Account::class, 12)->create([
            "account_type" => Account::CURRENT_LIABILITY,
            "code" => null
        ]);

        $account = Account::new(
            $this->faker->name,
            Account::CURRENT_LIABILITY,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        $this->assertEquals(2213, $account->code);
    }

    /**
     * Test Account opening balance
     *
     * @return void
     */
    public function testAccountOpeningBalance()
    {
        $account = Account::new(
            $this->faker->name,
            Account::INVENTORY,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "year" => date("Y"),
            "amount" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::C,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "year" => date("Y"),
            "amount" => 40
        ]);

        $this->assertEquals($account->openingBalance(date("Y")), 70);

        $account = Account::new(
            $this->faker->name,
            Account::CONTRA_ASSET,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account->save();

        $rate = factory(ExchangeRate::class)->create([
            "rate" => 25
        ]);

        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => $rate->id,
            "year" => Carbon::now()->addYear()->year,
            "amount" => 100
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::C,
            "exchange_rate_id" => $rate->id,
            "year" => Carbon::now()->addYear()->year,
            "amount" => 80
        ]);

        $account->openingBalance(Carbon::now()->addYear()->year);
        $this->assertEquals(5.60, $account->openingBalance(Carbon::now()->addYear()->year));
    }

    /**
     * Test Account closing balance
     *
     * @return void
     */
    public function testAccountClosingBalance()
    {
        $account = Account::new(
            $this->faker->name,
            Account::RECEIVABLE,
            $this->faker->sentence,
            factory(Category::class)->create(),
            null,
            6000
        );
        $account->save();

        factory(Ledger::class, 3)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::D,
            "date" => Carbon::now(),
            "amount" => 50
        ]);

        factory(Ledger::class, 2)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::C,
            "date" => Carbon::now(),
            "amount" => 40
        ]);

        $this->assertEquals($account->closingBalance(), 70);

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "year" => Carbon::now()->year,
            "amount" => 100
        ]);

        $account = Account::find($account->id);
        $this->assertEquals($account->closingBalance(), 170);
    }

    /**
     * Test Accounts section balances
     *
     * @return void
     */
    public function testAccountsSectionBalances()
    {
        $account1 = Account::new(
            $this->faker->name,
            Account::RECEIVABLE,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account1->save();

        $category1 = $account1->category->name;

        $account2 = Account::new(
            $this->faker->name,
            Account::RECEIVABLE,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account2->save();

        $category2 = $account2->category->name;

        $account3 = Account::new(
            $this->faker->name,
            Account::OPERATING_REVENUE,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account3->save();

        $category3 = $account3->category->name;

        $account4 = Account::new(
            $this->faker->name,
            Account::CONTROL_ACCOUNT,
            $this->faker->sentence,
            factory(Category::class)->create()
        );
        $account4->save();

        $category4 = $account4->category->name;

        factory(Balance::class, 3)->create([
            "year" => date("Y"),
            "account_id" => $account1->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 50
        ]);

        factory(Balance::class, 2)->create([
            "year" => date("Y"),
            "account_id" => $account1->id,
            "balance_type" => Balance::C,
            "exchange_rate_id" => factory('Ekmungai\IFRS\Models\ExchangeRate')->create([
                "rate" => 1
            ])->id,
            "amount" => 40
        ]);

        //Client Invoice Transaction
        $clientInvoice = ClientInvoice::new($account2, Carbon::now(), $this->faker->word);

        $line = LineItem::new(
            $account3,
            factory(Vat::class)->create(["rate" => 16]),
            100,
            1,
            $this->faker->sentence,
            $account4
        );

        $clientInvoice->addLineItem($line);

        $clientInvoice->post();

        $clients = Account::sectionBalances(Account::RECEIVABLE);

        $incomes = Account::sectionBalances(Account::OPERATING_REVENUE);

        $control = Account::sectionBalances(Account::CONTROL_ACCOUNT);

        $this->assertTrue(in_array($category1, array_keys($clients["sectionCategories"])));
        $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->id, $account1->id);
        $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->openingBalance, 70);
        $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->currentBalance, 0);
        $this->assertEquals($clients["sectionCategories"][$category1]["accounts"][0]->closingBalance, 70);
        $this->assertEquals($clients["sectionCategories"][$category1]["total"], 70);

        $this->assertTrue(in_array($category2, array_keys($clients["sectionCategories"])));
        $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->id, $account2->id);
        $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->openingBalance, 0);
        $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->currentBalance, 116);
        $this->assertEquals($clients["sectionCategories"][$category2]["accounts"][0]->closingBalance, 116);
        $this->assertEquals($clients["sectionCategories"][$category2]["total"], 116);

        $this->assertEquals($clients["sectionTotal"], 186);

        $this->assertTrue(in_array($category3, array_keys($incomes["sectionCategories"])));
        $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->id, $account3->id);
        $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->openingBalance, 0);
        $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->currentBalance, -100);
        $this->assertEquals($incomes["sectionCategories"][$category3]["accounts"][0]->closingBalance, -100);
        $this->assertEquals($incomes["sectionCategories"][$category3]["total"], -100);

        $this->assertEquals($incomes["sectionTotal"], -100);

        $this->assertTrue(in_array($category4, array_keys($control["sectionCategories"])));
        $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->id, $account4->id);
        $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->openingBalance, 0);
        $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->currentBalance, -16);
        $this->assertEquals($control["sectionCategories"][$category4]["accounts"][0]->closingBalance, -16);
        $this->assertEquals($control["sectionCategories"][$category4]["total"], -16);

        $this->assertEquals($control["sectionTotal"], -16);
    }

    /**
     * Test Missing Account Type.
     *
     * @return void
     */
    public function testMissingAccountType()
    {
        $account = new Account();
        $this->expectException(MissingAccountType::class);
        $this->expectExceptionMessage('Account type is Required');

        $account->save();
    }

    /**
     * Test Hanging Transactions.
     *
     * @return void
     */
    public function testHangingTransactions()
    {
        $account = Account::new(
            $this->faker->name,
            Account::RECEIVABLE,
            $this->faker->sentence,
            factory(Category::class)->create(),
            null,
            6000
        );
        $account->save();

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::D,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            "year" => Carbon::now()->year,
            "amount" => 100
        ]);

        $this->expectException(HangingTransactions::class);
        $this->expectExceptionMessage(
            'Account cannot be deleted because it has existing transactions in the current Reporting Period'
        );

        $account->delete();
    }
}
