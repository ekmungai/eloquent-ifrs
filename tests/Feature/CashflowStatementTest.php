<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\ExchangeRate;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;

use IFRS\Transactions\CreditNote;
use IFRS\Transactions\JournalEntry;
use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\CashPurchase;
use IFRS\Transactions\ClientInvoice;
use IFRS\Transactions\DebitNote;

use IFRS\Reports\CashFlowStatement;

class CashFlowStatementTest extends TestCase
{
    /**
     * Test Cash Flow Statement
     *
     * @return void
     */
    public function testCashFlowStatement()
    {
        $lastMonth = Carbon::now()->subMonths(1);
        $endDate = date("m") > 1 ? $lastMonth->toDateString() : date("Y-m")."-01";
        $cashFlowStatement = new CashFlowStatement($endDate);
        $cashFlowStatement->attributes();

        $bank = factory(Account::class)->create([
            'name' => 'Bank Account',
            'account_type' => Account::BANK,
            'category_id' => null,
        ]);

        factory(Balance::class)->create([
            "account_id" => $bank->id,
            "balance_type" => Balance::DEBIT,
            "exchange_rate_id" => factory(ExchangeRate::class)->create([
                "rate" => 1
            ])->id,
            'reporting_period_id' => $this->period->id,
            "currency_id" => $bank->currency_id,
            "balance" => 100
        ]);


        /*
         | ------------------------------
         | Operating Revenue Transactions
         | ------------------------------
         */

        $clientInvoice = new ClientInvoice([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 500,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $clientInvoice->addLineItem($lineItem);

        $clientInvoice->post();

        $creditNote = new CreditNote([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        /*
         | ------------------------------
         | Non Operating Revenue Transactions
         | ------------------------------
         */

        $journalEntry = new JournalEntry([
            "account_id" => $bank->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
            'currency_id' => $bank->currency_id,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 500,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::NON_OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        /*
         | ------------------------------
         | Operating Expense Transactions
         | ------------------------------
         */
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
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $bill->addLineItem($lineItem);
        $bill->post();

        /*
         | ------------------------------
         | Non Operating Expense Transactions
         | ------------------------------
         */
        $bill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::DIRECT_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $bill->addLineItem($lineItem);
        $bill->post();

        // Depreciation
        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::CONTRA_ASSET,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OTHER_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $cashPurchase = new CashPurchase([
            "account_id" => $bank->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $bank->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OTHER_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $cashPurchase->addLineItem($lineItem);
        $cashPurchase->post();

        $debitNote = new DebitNote([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 50,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0,
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OTHER_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        /*
         | ------------------------------
         | Current Asset Transaction
         | ------------------------------
         */

        $bill = new SupplierBill([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::INVENTORY,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $bill->addLineItem($lineItem);
        $bill->post();

        /*
         | ------------------------------
         | Current Liability Transaction
         | ------------------------------
         */

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::CURRENT_LIABILITY,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $bank->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => $bank->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        /*
         | ------------------------------
         | Non Current Asset Transaction
         | ------------------------------
         */

        $journalEntry = new JournalEntry([
            "account_id" => $bank->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $bank->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 150,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::NON_CURRENT_ASSET,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        /*
         | ------------------------------
         | Non Current Liability Transaction
         | ------------------------------
         */

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::NON_CURRENT_LIABILITY,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            'currency_id' => $bank->currency_id,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => $bank->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        /*
         | ------------------------------
         | Equity Transaction
         | ------------------------------
         */

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::EQUITY,
                'category_id' => null
            ])->id,
            "currency_id" => $bank->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 100,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => $bank->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $startDate = ReportingPeriod::periodStart();
        $endDate = ReportingPeriod::periodEnd();

        $sections = $cashFlowStatement->getSections($startDate, $endDate);
        $cashFlowStatement->toString();

        $provisions = CashFlowStatement::PROVISIONS;
        $receivables = CashFlowStatement::RECEIVABLES;
        $payables = CashFlowStatement::PAYABLES;
        $taxation = CashFlowStatement::TAXATION;
        $currentAssets = CashFlowStatement::CURRENT_ASSETS;
        $currentLiabilities = CashFlowStatement::CURRENT_LIABILITIES;
        $nonCurrentAssets = CashFlowStatement::NON_CURRENT_ASSETS;
        $nonCurrentLiabilities = CashFlowStatement::NON_CURRENT_LIABILITIES;
        $equity = CashFlowStatement::EQUITY;
        $profit = CashFlowStatement::PROFIT;
        $startCashBalance = CashFlowStatement::START_CASH_BALANCE;

        $endCashBalance = CashFlowStatement::END_CASH_BALANCE;
        $cashbookBalance = CashFlowStatement::CASHBOOK_BALANCE;
        $operationsCashFlow = CashFlowStatement::OPERATIONS_CASH_FLOW;
        $investmentCashFlow = CashFlowStatement::INVESTMENT_CASH_FLOW;
        $financingCashFlow = CashFlowStatement::FINANCING_CASH_FLOW;
        $netCashFlow = CashFlowStatement::NET_CASH_FLOW;

        $this->assertEquals(
            $sections,
            [
                "balances" => $cashFlowStatement->balances,
                "results" => $cashFlowStatement->results,
            ]
        );


        // Statement balances
        $this->assertEquals(
            $cashFlowStatement->balances[$provisions],
            50
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$receivables],
            -530
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$payables],
            298
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$taxation],
            112
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$currentAssets],
            -100
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$currentLiabilities],
            100
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$nonCurrentAssets],
            -150
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$nonCurrentLiabilities],
            100
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$equity],
            100
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$profit],
            700
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$startCashBalance],
            100
        );
        $this->assertEquals(
            $cashFlowStatement->balances[$netCashFlow],
            680
        );

        // Statement Results
        $this->assertEquals(
            $cashFlowStatement->results[$endCashBalance],
            780
        );
        $this->assertEquals(
            $cashFlowStatement->results[$cashbookBalance],
            780
        );
        $this->assertEquals(
            $cashFlowStatement->results[$operationsCashFlow],
            630
        );
        $this->assertEquals(
            $cashFlowStatement->results[$investmentCashFlow],
            -150
        );
        $this->assertEquals(
            $cashFlowStatement->results[$financingCashFlow],
            200
        );
    }
}
