<?php

namespace Tests\Unit;


use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use Carbon\Carbon;
use IFRS\Exceptions\InvalidAccountType;
use IFRS\Exceptions\InvalidPeriodStatus;
use IFRS\Exceptions\MissingClosingRate;

use IFRS\Models\RecycledObject;
use IFRS\Models\ReportingPeriod;
use IFRS\User;

use IFRS\Exceptions\MissingReportingPeriod;
use IFRS\Models\Account;
use IFRS\Models\Assignment;
use IFRS\Models\ClosingRate;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\Vat;

use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\ClientReceipt;
use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\SupplierPayment;

class ReportingPeriodTest extends TestCase
{
    /**
     * ReportingPeriod Model relationships test.
     *
     * @return void
     */
    public function testReportingPeriodRelationships()
    {
        $entity = Auth::user()->entity;

        $period = new ReportingPeriod([
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
        ]);
        $period->save();

        $period->attributes();
        $this->assertEquals($entity->reportingPeriods->last()->calendar_year, $period->calendar_year);
        $this->assertEquals(
            $period->toString(true),
            'ReportingPeriod: ' . $period->calendar_year
        );
        $this->assertEquals(
            $period->toString(),
            $period->calendar_year
        );
    }

    /**
     * Test ReportingPeriod model Entity Scope.
     *
     * @return void
     */
    public function testReportingPeriodEntityScope()
    {
        $user = factory(User::class)->create();
        $user->entity_id = 2;
        $user->save();

        $this->be($user);

        $this->assertEquals(count(ReportingPeriod::all()), 0);

        $this->be(User::withoutGlobalScopes()->find(1));
        $this->assertEquals(count(ReportingPeriod::all()), 1);
    }

    /**
     * Test ReportingPeriod Model recylcling
     *
     * @return void
     */
    public function testReportingPeriodRecycling()
    {
        $period = new ReportingPeriod([
            'period_count' => 1,
            'calendar_year' => Carbon::now()->year,
        ]);

        $period->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($period->recycled->first(), $recycled);
    }

    /**
     * Test ReportingPeriod Dates
     *
     * @return void
     */
    public function testReportingPeriodDates()
    {
        $this->assertEquals(ReportingPeriod::year(), date("Y"));
        $this->assertEquals(ReportingPeriod::year("2025-06-25"), "2025");
        $this->assertEquals(ReportingPeriod::periodStart("2025-06-25")->toDateString(), "2025-01-01");

        $entity = Auth::user()->entity;
        $entity->year_start = 4;
        $entity->save();

        $this->assertEquals(ReportingPeriod::year("2025-03-25"), "2024");
        $this->assertEquals(ReportingPeriod::periodStart("2025-03-25")->toDateString(), "2024-04-01");
        $this->assertEquals(ReportingPeriod::periodEnd("2025-03-25")->toDateString(), "2025-03-31");
    }

    /**
     * Test Missing Report Period.
     *
     * @return void
     */
    public function testMissingReportPeriod()
    {
        $this->expectException(MissingReportingPeriod::class);
        $this->expectExceptionMessage('has no reporting period defined for the year');

        ReportingPeriod::getPeriod(Carbon::parse("1970-01-01"));
    }

    /**
     * Test Transaction Curencies.
     *
     * @return void
     */
    public function testTransactionCurencies()
    {        
        $currency1 = factory(Currency::class)->create();

         // Receivables
         $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 110
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);

        
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new ClientInvoice([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100
            ])->id,
            'currency_id' => $currency1->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();
        
        $forex = factory(Account::class)->create([
            'account_type' => Account::NON_OPERATING_REVENUE,
            'category_id' => null
        ]);

        $assignment = new Assignment([
            'assignment_date' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'cleared_id' => $cleared->id,
            'cleared_type' => $cleared->cleared_type,
            'amount' => 100,
            'forex_account_id' => $forex->id,
        ]);
        $assignment->save();

        $this->assertEquals($forex->Closingbalance(), [$this->reportingCurrencyId => -1000]);

        $currency2 = factory(Currency::class)->create();

        // Payables
        $account2 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency2->id,
        ]);

        $transaction = new SupplierPayment([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 100,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency2->id,
            ])->id,
            'amount' => 50,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $cleared = new SupplierBill([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);

        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null
            ])->id,
            'amount' => 100,
        ]);

        $cleared->addLineItem($line);
        $cleared->post();

        $currencies = $this->period->transactionCurrencies();
        $this->assertEquals(count($currencies), 2);
        $this->assertEquals($currencies[0]->currency_id, $currency1->id);
        $this->assertEquals($currencies[1]->currency_id, $currency2->id);

        $currencies = $this->period->transactionCurrencies($account1->id);

        $this->assertEquals(count($currencies), 1);
        $this->assertEquals($currencies[0]->currency_id, $currency1->id);

        $currencies = $this->period->transactionCurrencies($account2->id);

        $this->assertEquals(count($currencies), 1);
        $this->assertEquals($currencies[0]->currency_id, $currency2->id);
    }

    /**
     * Test Invalid Account Type.
     *
     * @return void
     */
    public function testInvalidAccountType()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null
        ]);
        $vatId = factory(Vat::class)->create(["rate" => 0])->id;

        $this->expectException(InvalidAccountType::class);
        $this->expectExceptionMessage('Transaltion Forex Account must be of Type Equity');

        $this->period->prepareBalancesTranslation($forex->id, $vatId);
        
    }

    /**
     * Test Invalid Period Status.
     *
     * @return void
     */
    public function testPeriodStatus()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);
        $vatId = factory(Vat::class)->create(["rate" => 0])->id;

        $this->expectException(InvalidPeriodStatus::class);
        $this->expectExceptionMessage('Reporting Period must have Adjusting status to translate foreign balances');

        $this->period->prepareBalancesTranslation($forex->id, $vatId);
        
    }

    /**
     * Test Missing Closing Rate.
     *
     * @return void
     */
    public function testMissingClosingRate()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);
        $vatId = factory(Vat::class)->create(["rate" => 0])->id;

        $this->period->status = ReportingPeriod::ADJUSTING;

        $this->assertEquals($this->period->prepareBalancesTranslation($forex->id, $vatId), []);

        $currency1 = factory(Currency::class)->create();
        
        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->expectException(MissingClosingRate::class);
        $this->expectExceptionMessage('Closing Rate for ' . $currency1->currency_code . ' is missing ');

        $this->period->prepareBalancesTranslation($forex->id, $vatId);
    }

    /**
     * Test Balances Translation.
     *
     * @return void
     */
    public function testBalancesTranslation()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);
        $vatId = factory(Vat::class)->create(["rate" => 0])->id;

        $currency1 = factory(Currency::class)->create();
        ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new ClientReceipt([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->period->status = ReportingPeriod::ADJUSTING;

        $transactions = $this->period->prepareBalancesTranslation($forex->id, $vatId);

        $this->assertEquals($transactions[0]->account->id, $account1->id);
        $this->assertTrue($transactions[0]->credited);
        $this->assertEquals($transactions[0]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[0]->amount, 1000);
        $this->assertFalse($transactions[0]->is_posted);

        $this->assertEquals($transactions[1]->account->id, $account2->id);
        $this->assertFalse($transactions[1]->credited);
        $this->assertEquals($transactions[1]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[1]->amount, 1000);
        $this->assertFalse($transactions[1]->is_posted);

        // Payables
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account4 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new SupplierBill([
            "account_id" => $account3->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new SupplierBill([
            "account_id" => $account4->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transactions = $this->period->prepareBalancesTranslation($forex->id, $vatId);

        $this->assertEquals($transactions[0]->account->id, $account3->id);
        $this->assertFalse($transactions[0]->credited);
        $this->assertEquals($transactions[0]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[0]->amount, 1000);
        $this->assertFalse($transactions[0]->is_posted);

        $this->assertEquals($transactions[1]->account->id, $account4->id);
        $this->assertTrue($transactions[1]->credited);
        $this->assertEquals($transactions[1]->narration, $currency1->currency_code . " ". $this->period->calendar_year . " Forex Balance Translation");
        $this->assertEquals($transactions[1]->amount, 1000);
        $this->assertFalse($transactions[1]->is_posted);
    }

    /**
     * Test Closing Transactions.
     *
     * @return void
     */
    public function testClosingTransactions()
    {
        $forex = factory(Account::class)->create([
            'account_type' => Account::EQUITY,
            'category_id' => null
        ]);
        $vatId = factory(Vat::class)->create(["rate" => 0])->id;

        $currency1 = factory(Currency::class)->create();
        $closingRate1 = ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency1->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        // Receivables
        $account1 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account2 = factory(Account::class)->create([
            'account_type' => Account::RECEIVABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new ClientReceipt([
            "account_id" => $account2->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        // Payables
        $account3 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);
        $account4 = factory(Account::class)->create([
            'account_type' => Account::PAYABLE,
            'category_id' => null,
            'currency_id' => $currency1->id,
        ]);

        $transaction = new SupplierBill([
            "account_id" => $account3->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 110,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $transaction = new SupplierBill([
            "account_id" => $account4->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency1->id,
            ])->id,
            'currency_id' => $currency1->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'currency_id' => $currency1->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $currency2 = factory(Currency::class)->create();

        $closingRate2 = ClosingRate::create([
            'exchange_rate_id' => factory(ExchangeRate::class)->create([
                'currency_id' => $currency2->id,
                "rate" => 100,
            ])->id,
            'reporting_period_id' => $this->period->id,
        ]);

        $transaction = new ClientReceipt([
            "account_id" => $account1->id,
            "transaction_date" => Carbon::now(),
            "narration" => $this->faker->word,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 90,
                'currency_id' => $currency2->id,
            ])->id,
            'currency_id' => $currency2->id,
        ]);
        
        $line = new LineItem([
            'vat_id' => factory(Vat::class)->create(["rate" => 0])->id,
            'account_id' => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency2->id,
            ])->id,
            'amount' => 100,
        ]);
        
        $transaction->addLineItem($line);
        $transaction->post();

        $this->period->status = ReportingPeriod::ADJUSTING;
        
        $this->period->prepareBalancesTranslation($forex->id, $vatId);

        $transactions = $this->period->getTranslations();

        $this->assertEquals(
            $transactions[$account1->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => false
                ],
                [
                    "currency" => $currency2->currency_code,
                    "closingRate" => $closingRate2->exchangeRate->rate,
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account2->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account3->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => false
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account4->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => false
                ],
            ] 
        );

        $this->period->postTranslations();

        $transactions = $this->period->getTranslations();

        $this->assertEquals(
            $transactions[$account1->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => true
                ],
                [
                    "currency" => $currency2->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account2->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account3->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -11000,
                    "foreignBalance" => -10000,
                    "translation" => 1000.0,
                    "posted" => true
                ],
            ] 
        );
        $this->assertEquals(
            $transactions[$account4->name],[
                [
                    "currency" => $currency1->currency_code,
                    "closingRate" => $closingRate1->exchangeRate->rate,
                    "closingRate" => "100",
                    "closingRate" => "100",
                    "currencyBalance" => -100,
                    "localBalance" => -9000,
                    "foreignBalance" => -10000,
                    "translation" => -1000.0,
                    "posted" => true
                ],
            ] 
        );
    }
}
