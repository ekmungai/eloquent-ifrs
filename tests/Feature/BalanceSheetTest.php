<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Faker\Factory;

use Illuminate\Support\Facades\Auth;

use IFRS\Tests\TestCase;

use IFRS\Models\Transaction;
use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Category;
use IFRS\Models\Currency;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;

use IFRS\Reports\BalanceSheet;

use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\CashSale;
use IFRS\Transactions\JournalEntry;

class BalanceSheetTest extends TestCase
{
    /**
     * Test Balance Sheet
     *
     * @return void
     */
    public function testBalanceSheet()
    {
        $balanceSheet = new BalanceSheet();
        $balanceSheet->attributes();

        factory(Balance::class)->create([

            "account_id" => factory(Account::class)->create([
                "account_type" => Account::INVENTORY,
                'category_id' => null
            ])->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 100
        ]);

        factory(Balance::class)->create([
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::CURRENT_LIABILITY,
                'category_id' => null
            ])->id,
            "balance_type" => Balance::CREDIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "balance" => 100
        ]);

        $bill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 100,
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();

        $bill->addLineItem($lineItem);
        $bill->post();
        
        $currency = factory(Currency::class)->create();
        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null,
                'currency_id' => $currency->id,
            ])->id,
            "date" => Carbon::now(),
            'currency_id' => $currency->id,
            "narration" => $this->faker->word,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 200,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $lineItem->addVat(
            factory(Vat::class)->create([
                "rate" => 16
            ])
        );
        $lineItem->save();

        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::EQUITY,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 70,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::RECONCILIATION,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $startDate = ReportingPeriod::periodStart();
        $endDate = ReportingPeriod::periodEnd();

        $sections = $balanceSheet->getSections($startDate, $endDate);
        $balanceSheet->toString();

        $assets = BalanceSheet::ASSETS;
        $liabilities = BalanceSheet::LIABILITIES;
        $reconciliation = BalanceSheet::RECONCILIATION;
        $equity = BalanceSheet::EQUITY;

        $this->assertEquals(
            $sections,
            [
                "accounts" => $balanceSheet->accounts,
                "balances" => $balanceSheet->balances,
                "results" => $balanceSheet->results,
                "totals" => $balanceSheet->totals,
            ]
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::INVENTORY],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::BANK],
            232
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::NON_CURRENT_ASSET],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CONTROL],
            -16
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CURRENT_LIABILITY],
            -100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::PAYABLE],
            -116
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][Account::EQUITY],
            70
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][BalanceSheet::NET_PROFIT],
            -200
        );

        $this->assertEquals(
            $balanceSheet->balances[$reconciliation][Account::RECONCILIATION],
            -70
        );
    }


    public function testBalanceSheetLoggedOut()
    {
        $faker = Factory::create();

        $entity = Auth::user()->entity;
        Auth::logout(); //log out user

        $balanceSheet = new BalanceSheet(null,$entity);
        $balanceSheet->attributes();

        Balance::create([
           'account_id' => Account::create([
               'account_type' => Account::INVENTORY,
               'category_id' => factory(Category::class)->create([
                    'category_type' => Account::INVENTORY,
                    'entity_id' => $entity->id
                ])->id,
               'entity_id' => $entity->id
           ])->id,
            'balance_type' => Balance::DEBIT,
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $faker->name,
                    'currency_code' => $faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $faker->word,
            'transaction_type' => $faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
            'reference' => $faker->word,
            'balance' => 100,
            'entity_id' => $entity->id

        ]);

        Balance::create([
            'account_id' => Account::create([
                'account_type' => Account::CURRENT_LIABILITY,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::CURRENT_LIABILITY,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id
            ])->id,
            'balance_type' => Balance::CREDIT,
            'exchange_rate_id' => ExchangeRate::create([
                'valid_from' => $faker->dateTimeThisMonth(),
                'valid_to' => Carbon::now(),
                'currency_id' => Currency::create([
                    'name' => $faker->name,
                    'currency_code' => $faker->currencyCode,
                    'entity_id' => $entity->id
                ])->id,
                'rate' => 1,
                'entity_id' => $entity->id
            ])->id,
            'reporting_period_id' => $this->period->id,
            'transaction_date' => Carbon::now()->subYears(1.5),
            'transaction_no' => $faker->word,
            'transaction_type' => $faker->randomElement([
                Transaction::IN,
                Transaction::BL,
                Transaction::JN
            ]),
            'reference' => $faker->word,
            'balance' => 100,
            'entity_id' => $entity->id

        ]);


        $bill = new SupplierBill([
            "account_id" => Account::create([
                'account_type' => Account::PAYABLE,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::PAYABLE,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'entity_id' => $entity->id
        ]);


        $vat = Vat::create([
            'name' => $faker->name,
            'code' => $faker->randomLetter(),
            'entity_id' => $entity->id,
            'rate' => 16,
            'account_id' => Account::create([
                'account_type' => Account::CONTROL,
                'category_id' => null,
                'entity_id' => $entity->id
            ])->id
        ]);
        $lineItem = LineItem::create([
            'amount' => 100,
            "account_id" => Account::create([
                'account_type' => Account::NON_CURRENT_ASSET,
                'category_id' => null,
                'entity_id' => $entity->id
            ])->id,
            "quantity" => 1,
            "entity_id" => $entity->id
        ]);
        $lineItem->addVat($vat);

        $bill->addLineItem($lineItem);
        $bill->post();

        $currency = factory(Currency::class)->create([
            "entity_id" => $entity->id
        ]);

        $cashSale = new CashSale([
            "account_id" => Account::create([
                'account_type' => Account::BANK,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::BANK,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id,
                'currency_id' => $currency->id,
            ])->id,
            "date" => Carbon::now(),
            'currency_id' => $currency->id,
            "narration" => $this->faker->word,
            "entity_id" => $entity->id
        ]);

        $lineItem = LineItem::create([
            'amount' => 200,
            "account_id" => Account::create([
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::OPERATING_REVENUE,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id,
            ])->id,
            "quantity" => 1,
            "entity_id" => $entity->id
        ]);

        $vat2 = Vat::create([
            "rate" => 16,
            "name" => 'Test Vat',
            "code" => 'T',
            "account_id" => Account::create([
                'account_type' => Account::CONTROL,
                'category_id' => null,
                'entity_id' => $entity->id,
            ])->id,
            'entity_id' => $entity->id,
        ]);
        $lineItem->addVat($vat2);
        $lineItem->save();

        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $journalEntry = new JournalEntry([
            "account_id" => Account::create([
                'account_type' => Account::EQUITY,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::EQUITY,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id,
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
            "entity_id" => $entity->id
        ]);

        $lineItem = LineItem::create([
            'amount' => 70,
            "account_id" =>  Account::create([
                'account_type' => Account::RECONCILIATION,
                'category_id' => factory(Category::class)->create([
                    'category_type' => Account::RECONCILIATION,
                    'entity_id' => $entity->id
                ])->id ,
                'entity_id' => $entity->id,
            ])->id,
            "quantity" => 1,
            "entity_id" => $entity->id
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $startDate = ReportingPeriod::periodStart(null, $entity);
        $endDate = ReportingPeriod::periodEnd(null, $entity);

        $sections = $balanceSheet->getSections($startDate, $endDate);
        $balanceSheet->toString();

        $assets = BalanceSheet::ASSETS;
        $liabilities = BalanceSheet::LIABILITIES;
        $reconciliation = BalanceSheet::RECONCILIATION;
        $equity = BalanceSheet::EQUITY;

        $this->assertEquals(
            $sections,
            [
                "accounts" => $balanceSheet->accounts,
                "balances" => $balanceSheet->balances,
                "results" => $balanceSheet->results,
                "totals" => $balanceSheet->totals,
            ]
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::INVENTORY],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::BANK],
            232
        );

        $this->assertEquals(
            $balanceSheet->balances[$assets][Account::NON_CURRENT_ASSET],
            100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CONTROL],
            -16
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::CURRENT_LIABILITY],
            -100
        );

        $this->assertEquals(
            $balanceSheet->balances[$liabilities][Account::PAYABLE],
            -116
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][Account::EQUITY],
            70
        );

        $this->assertEquals(
            $balanceSheet->balances[$equity][BalanceSheet::NET_PROFIT],
            -200
        );

        $this->assertEquals(
            $balanceSheet->balances[$reconciliation][Account::RECONCILIATION],
            -70
        );
    }
}
