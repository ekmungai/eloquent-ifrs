<?php

namespace Tests\Feature;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Vat;
use IFRS\Reports\IncomeStatement;

use IFRS\Transactions\CashSale;
use IFRS\Transactions\CreditNote;
use IFRS\Transactions\JournalEntry;
use IFRS\Transactions\SupplierBill;
use IFRS\Transactions\CashPurchase;
use IFRS\Transactions\DebitNote;

class IncomeStatementTest extends TestCase
{
    /**
     * Test Income Statement
     *
     * @return void
     */
    public function testIncomeStatement()
    {
        $incomeStatement = new IncomeStatement();
        $incomeStatement->attributes();

        /*
         | ------------------------------
         | Operating Revenue Transactions
         | ------------------------------
         */

        $cashSale = new CashSale([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 200,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 16
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OPERATING_REVENUE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $cashSale->addLineItem($lineItem);

        $cashSale->post();

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
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            "credited" => false,
        ]);

        $lineItem =  factory(LineItem::class)->create([
            "amount" => 200,
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
         | None Operating Expense Transactions
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
            "amount" => 70,
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

        $journalEntry = new JournalEntry([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 70,
            "vat_id" => factory(Vat::class)->create([
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OVERHEAD_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $cashPurchase = new CashPurchase([
            "account_id" => factory(Account::class)->create([
                'account_type' => Account::BANK,
                'category_id' => null
            ])->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
        ]);

        $lineItem = factory(LineItem::class)->create([
            "amount" => 70,
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
                "rate" => 0
            ])->id,
            "account_id" => factory(Account::class)->create([
                "account_type" => Account::OTHER_EXPENSE,
                'category_id' => null
            ])->id,
            "quantity" => 1,
        ]);
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        $startDate = ReportingPeriod::periodStart();
        $endDate = ReportingPeriod::periodEnd();

        $incomeStatement->getSections($startDate, $endDate, false);

        $incomeStatement->toString();

        $operatingRevenues = IncomeStatement::OPERATING_REVENUES;
        $operatingExpenses = IncomeStatement::OPERATING_EXPENSES;
        $nonOperatingRevenues = IncomeStatement::NON_OPERATING_REVENUES;
        $nonOperatingExpenses = IncomeStatement::NON_OPERATING_EXPENSES;

        $this->assertEquals(
            $incomeStatement->balances[$operatingRevenues][Account::OPERATING_REVENUE],
            -150
        );

        $this->assertEquals(
            $incomeStatement->balances[$operatingExpenses][Account::OPERATING_EXPENSE],
            100
        );

        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingRevenues][Account::NON_OPERATING_REVENUE],
            -200
        );

        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::DIRECT_EXPENSE],
            70
        );

        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::OVERHEAD_EXPENSE],
            70
        );

        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::OTHER_EXPENSE],
            20
        );

        $results = IncomeStatement::getResults(date('m'),date('y'));

        $this->assertEquals(
            $results[IncomeStatement::OPERATING_REVENUES],
            150
        );

        $this->assertEquals(
            $results[IncomeStatement::NON_OPERATING_REVENUES],
            200
        );

        $this->assertEquals(
            $results[IncomeStatement::OPERATING_EXPENSES],
            100
        );

        $this->assertEquals(
            $results[IncomeStatement::GROSS_PROFIT],
            250
        );

        $this->assertEquals(
            $results[IncomeStatement::NON_OPERATING_EXPENSES],
            160
        );

        $this->assertEquals(
            $results[IncomeStatement::NET_PROFIT],
            90
        );
    }
}
