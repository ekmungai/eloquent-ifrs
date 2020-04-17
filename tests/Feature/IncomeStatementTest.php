<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Tests\TestCase;

use App\Models\Account;
use App\Models\LineItem;

use App\Reports\IncomeStatement;

use App\Transactions\CashSale;
use App\Transactions\CreditNote;
use App\Transactions\JournalEntry;
use App\Transactions\SupplierBill;
use App\Transactions\CashPurchase;
use App\Transactions\DebitNote;

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

        /*
         | ------------------------------
         | Operating Revenue Transactions
         | ------------------------------
         */

        $cashSale = CashSale::new(
            factory(Account::class)->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            200,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $cashSale->addLineItem($lineItem);

        $cashSale->post();

        $creditNote = CreditNote::new(
            factory(Account::class)->create([
                'account_type' => Account::RECEIVABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OPERATING_REVENUE
            ]),
            factory('App\Models\Vat')->create(["rate" => 0]),
            50
        );
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        /*
         | ------------------------------
         | Non Operating Revenue Transactions
         | ------------------------------
         */

        $journalEntry = JournalEntry::new(
            factory(Account::class)->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::NON_OPERATING_REVENUE
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            200,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $journalEntry->setCredited(false);
        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        /*
         | ------------------------------
         | Operating Expense Transactions
         | ------------------------------
         */
        $bill = SupplierBill::new(
            factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OPERATING_EXPENSE
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            100,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $bill->addLineItem($lineItem);
        $bill->post();

        /*
         | ------------------------------
         | None Operating Expense Transactions
         | ------------------------------
         */
        $bill = SupplierBill::new(
            factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem =  LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::DIRECT_EXPENSE
            ]),
            factory('App\Models\Vat')->create(["rate" => 16]),
            70,
            1,
            null,
            factory('App\Models\Account')->create([
                "account_type" => Account::CONTROL_ACCOUNT
            ])
        );
        $bill->addLineItem($lineItem);
        $bill->post();

        $journalEntry = JournalEntry::new(
            factory(Account::class)->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OVERHEAD_EXPENSE
            ]),
            factory('App\Models\Vat')->create(["rate" => 0]),
            70
        );

        $journalEntry->addLineItem($lineItem);
        $journalEntry->post();

        $cashPurchase = CashPurchase::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::BANK,
            ]),
            Carbon::now(),
            $this->faker->word
        );
        $lineItem = LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OTHER_EXPENSE
            ]),
            factory('App\Models\Vat')->create(["rate" => 0]),
            70
        );
        $cashPurchase->addLineItem($lineItem);
        $cashPurchase->post();

        $debitNote = DebitNote::new(
            factory('App\Models\Account')->create([
                'account_type' => Account::PAYABLE,
            ]),
            Carbon::now(),
            $this->faker->word
        );

        $lineItem = LineItem::new(
            factory('App\Models\Account')->create([
                "account_type" => Account::OTHER_EXPENSE
            ]),
            factory('App\Models\Vat')->create(["rate" => 0]),
            50
        );
        $debitNote->addLineItem($lineItem);

        $debitNote->post();

        $incomeStatement->getSections();

        $operatingRevenues = IncomeStatement::OPERATING_REVENUES;
        $operatingExpenses = IncomeStatement::OPERATING_EXPENSES;
        $nonOperatingRevenues = IncomeStatement::NON_OPERATING_REVENUES;
        $nonOperatingExpenses = IncomeStatement::NON_OPERATING_EXPENSES;

        $this->assertEquals(
            count($incomeStatement->accounts[$operatingRevenues][Account::OPERATING_REVENUE]),
            2
        );
        $this->assertEquals(
            $incomeStatement->balances[$operatingRevenues][Account::OPERATING_REVENUE],
            -150
        );

        $this->assertEquals(
            count($incomeStatement->accounts[$operatingExpenses][Account::OPERATING_EXPENSE]),
            1
        );
        $this->assertEquals(
            $incomeStatement->balances[$operatingExpenses][Account::OPERATING_EXPENSE],
            100
        );

        $this->assertEquals(
            count($incomeStatement->accounts[$nonOperatingRevenues][Account::NON_OPERATING_REVENUE]),
            1
        );
        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingRevenues][Account::NON_OPERATING_REVENUE],
            -200
        );

        $this->assertEquals(
            count($incomeStatement->accounts[$nonOperatingExpenses][Account::DIRECT_EXPENSE]),
            1
        );
        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::DIRECT_EXPENSE],
            70
        );

        $this->assertEquals(
            count($incomeStatement->accounts[$nonOperatingExpenses][Account::OVERHEAD_EXPENSE]),
            1
        );
        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::OVERHEAD_EXPENSE],
            70
        );

        $this->assertEquals(
            count($incomeStatement->accounts[$nonOperatingExpenses][Account::OTHER_EXPENSE]),
            2
        );
        $this->assertEquals(
            $incomeStatement->balances[$nonOperatingExpenses][Account::OTHER_EXPENSE],
            20
        );
    }
}
