<?php

namespace Tests\Unit;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\User;

use IFRS\Models\Account;
use IFRS\Models\Category;
use IFRS\Models\Currency;
use IFRS\Models\RecycledObject;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\Ledger;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;
use IFRS\Models\LineItem;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\SupplierBill;

use IFRS\Exceptions\HangingTransactions;
use IFRS\Exceptions\InvalidCategoryType;
use IFRS\Exceptions\MissingAccountType;

class AccountTest extends TestCase
{
    /**
     * Account Model relationships test.
     *
     * @return void
     */
    public function testAccountRelationships()
    {
        $currency = factory(Currency::class)->create();

        $category = factory(Category::class)->create();
        $type = $this->faker->randomElement(array_keys(config('ifrs')['accounts']));
        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => $type,
            'currency_id' => $currency->id,
            'code' => $this->faker->randomDigit,
            'category_id' => $category->id
        ]);
        $account->save();

        $this->assertEquals($account->currency->name, $currency->name);
        $this->assertEquals($account->category->name, $category->name);
        $this->assertEquals(
            $account->toString(true),
            Account::getType($account->account_type) . ' Account: ' . $account->name
        );
        $this->assertEquals($account->toString(), $account->name);
        $this->assertEquals($account->type, Account::getType($type));
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

        $account = new Account([
            'name' => $this->faker->name,
            'currency_id' => factory(Currency::class)->create()->id,
            'account_type' => $this->faker->randomElement(array_keys(config('ifrs')['accounts'])),
            'category_id' => factory(Category::class)->create()->id
        ]);

        $account->save();

        $this->assertEquals(count(Account::all()), 1);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(Account::all()), 0);
    }

    /**
     * Test Account Type Names
     *
     * @return void
     */
    public function testAccountTypeNames()
    {
        $this->assertEquals(Account::getTypes([Account::BANK, Account::RECEIVABLE]), ["Bank", "Receivable"]);
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
        $account = new Account([
            'name' => $this->faker->name,
            'code' => 6000,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        $this->assertEquals(6000, $account->code);

        // Auto generated code
        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::NON_CURRENT_ASSET,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        $this->assertEquals(1, $account->code);

        factory(Account::class, 3)->create([
            "account_type" => Account::OPERATING_REVENUE,
            "code" => null
        ]);

        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::OPERATING_REVENUE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        $this->assertEquals(4004, $account->code);

        factory(Account::class, 12)->create([
            "account_type" => Account::CURRENT_LIABILITY,
            "code" => null
        ]);

        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::CURRENT_LIABILITY,
            'category_id' => factory(Category::class)->create()->id
        ]);

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
        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::INVENTORY,
            'category_id' => factory(Category::class)->create()->id
        ]);

        $account->save();

        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 50
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 40
        ]);

        $this->assertEquals($account->openingBalance(), 70);

        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::CONTRA_ASSET,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        $rate = factory(ExchangeRate::class)->create([
            "rate" => 25
        ]);

        $reportingPeriod = factory(ReportingPeriod::class)->create([
            "calendar_year" => Carbon::now()->addYear()->year,
        ]);
        factory(Balance::class, 3)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => $rate->id,
            'reporting_period_id' => $reportingPeriod->id,
            "amount" => 100
        ]);

        factory(Balance::class, 2)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => $rate->id,
            'reporting_period_id' => $reportingPeriod->id,
            "amount" => 80
        ]);

        $this->assertEquals(5.60, $account->openingBalance(Carbon::now()->addYear()->year));
    }

    /**
     * Test Account closing balance
     *
     * @return void
     */
    public function testAccountClosingBalance()
    {
        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        factory(Ledger::class, 3)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::DEBIT,
            "date" => Carbon::now(),
            "amount" => 50
        ]);

        factory(Ledger::class, 2)->create([
            "post_account" => $account->id,
            "entry_type" => Balance::CREDIT,
            "date" => Carbon::now(),
            "amount" => 40
        ]);

        $this->assertEquals($account->closingBalance(), 70);

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
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
        $account1 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account1->save();

        $category1 = $account1->category->name;

        $account2 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account2->save();

        $category2 = $account2->category->name;

        $account3 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::OPERATING_REVENUE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account3->save();

        $category3 = $account3->category->name;

        $account4 = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::CONTROL,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account4->save();

        $category4 = $account4->category->name;

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
            "account_id" => $account2->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create([
                "rate" => 16,
                "account_id" => $account4->id
            ])->id,
            'account_id' => $account3->id,
            'narration' => $this->faker->sentence,
            'quantity' => $this->faker->randomNumber(),
            'amount' => 100,
            'quantity' => 1,
        ]);

        $clientInvoice->addLineItem($line);

        $clientInvoice->post();

        $clients = Account::sectionBalances([Account::RECEIVABLE]);

        $incomes = Account::sectionBalances([Account::OPERATING_REVENUE]);

        $control = Account::sectionBalances([Account::CONTROL]);

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
        $account = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $account->save();

        factory(Balance::class)->create([
            "account_id" => $account->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 100
        ]);

        $this->expectException(HangingTransactions::class);
        $this->expectExceptionMessage(
            'Account cannot be deleted because it has existing Transactions/Balances in the current Reporting Period'
        );

        $account->delete();
    }

    /**
     * Test Account movement.
     *
     * @return void
     */
    public function testAccountMovement()
    {
        $client = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $client->save();

        factory(Balance::class)->create([
            "account_id" => $client->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 100
        ]);

        $this->assertEquals(Account::movement([Account::RECEIVABLE]), 0);
        $this->assertEquals(Account::movement([Account::CONTROL]), 0);

        $revenue = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::OPERATING_REVENUE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $revenue->save();

        $vat = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::CONTROL,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $vat->save();

        //Client Invoice Transaction
        $clientInvoice = new ClientInvoice([
            "account_id" => $client->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create([
                "rate" => 16,
                "account_id" => $vat->id
            ])->id,
            'account_id' => $revenue->id,
            'narration' => $this->faker->sentence,
            'quantity' => $this->faker->randomNumber(),
            'amount' => 100,
            'quantity' => 1,
        ]);

        $clientInvoice->addLineItem($line);
        $clientInvoice->post();

        $this->assertEquals(Account::movement([Account::RECEIVABLE]), -116);
        $this->assertEquals(Account::movement([Account::CONTROL]), 16);

        $supplier = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::PAYABLE,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $supplier->save();

        $asset = new Account([
            'name' => $this->faker->name,
            'account_type' => Account::NON_CURRENT_ASSET,
            'category_id' => factory(Category::class)->create()->id
        ]);
        $asset->save();

        factory(Balance::class)->create([
            "account_id" => $supplier->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 50
        ]);

        factory(Balance::class)->create([
            "account_id" => $asset->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1,
            ])->id,
            'reporting_period_id' => $this->period->id,
            "amount" => 50
        ]);

        $this->assertEquals(Account::movement([Account::PAYABLE]), 0);
        $this->assertEquals(Account::movement([Account::NON_CURRENT_ASSET]), 0);

        //Supplier Bill Transaction
        $SupplierBill = new SupplierBill([
            "account_id" => $supplier->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create([
                "rate" => 16,
                "account_id" => $vat->id
            ])->id,
            'account_id' => $asset->id,
            'narration' => $this->faker->sentence,
            'quantity' => $this->faker->randomNumber(),
            'amount' => 50,
            'quantity' => 1,
        ]);

        $SupplierBill->addLineItem($line);
        $SupplierBill->post();

        $this->assertEquals(Account::movement([Account::PAYABLE]), 58);
        $this->assertEquals(Account::movement([Account::NON_CURRENT_ASSET]), -50);
        $this->assertEquals(Account::movement([Account::CONTROL]), 8);
    }

    /**
     * Test Invalid Category Type.
     *
     * @return void
     */
    public function testInvalidCategoryType()
    {
        $account = new Account([
            'account_type' => Account::RECEIVABLE,
            'category_id' => factory(Category::class)->create([
                'category_type' => Account::PAYABLE,
            ])->id
        ]);
        $this->expectException(InvalidCategoryType::class);
        $this->expectExceptionMessage('Cannot assign Receivable Account to Payable Category');

        $account->save();
    }
}
